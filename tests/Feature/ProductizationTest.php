<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\User;
use App\Services\BackupService;
use App\Services\DemoData;
use App\Support\BusinessProfile;
use App\Support\EnvWriter;
use App\Support\FirstRun;
use App\Support\Installer;
use App\Support\License;
use App\Support\UpdateChecker;
use Database\Seeders\RolesSeeder;
use Dotenv\Dotenv;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class ProductizationTest extends TestCase
{
    use RefreshDatabase;

    private string $tmp;

    protected function setUp(): void
    {
        parent::setUp();
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
        BusinessProfile::apply('general');
        setting()->set('store.timezone', 'Asia/Karachi');
        License::reset();

        $this->tmp = sys_get_temp_dir().'/nexcart-prod-'.bin2hex(random_bytes(4));
        File::ensureDirectoryExists($this->tmp);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tmp);
        License::reset();
        parent::tearDown();
    }

    private function user(string $role): User
    {
        return tap(User::factory()->create(), fn ($u) => $u->assignRole($role));
    }

    /** Point storage at a scratch folder so backups never touch the real one. */
    private function scratchStorage(): void
    {
        File::ensureDirectoryExists($this->tmp.'/storage/app/public');
        File::ensureDirectoryExists($this->tmp.'/storage/app/private');
        $this->app->useStoragePath($this->tmp.'/storage');
    }

    /** A real on-disk SQLite connection (the test default is in-memory, which cannot be copied). */
    private function fileDb(string $name = 'bk'): Connection
    {
        config(["database.connections.{$name}" => ['driver' => 'sqlite', 'database' => $this->tmp."/{$name}.sqlite", 'prefix' => '', 'foreign_key_constraints' => true]]);
        touch($this->tmp."/{$name}.sqlite");
        $db = DB::connection($name);
        $db->statement('create table notes (id integer primary key autoincrement, body text)');
        $db->table('notes')->insert(['body' => 'hello']);

        return $db;
    }

    // ================= security headers =================

    public function test_every_page_carries_security_headers_and_hsts_only_on_https(): void
    {
        $r = $this->get('/shop');
        $r->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'SAMEORIGIN')->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString('camera=()', $r->headers->get('Permissions-Policy'));
        $this->assertFalse($r->headers->has('Strict-Transport-Security'));

        $this->get('https://shop.test/shop')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $this->get('/login')->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    // ================= system health & updates =================

    public function test_system_page_is_owner_only_and_reports_checks(): void
    {
        $this->actingAs($this->user('manager'))->get('/admin/system')->assertForbidden();
        $this->actingAs($this->user('staff'))->get('/admin/backups')->assertForbidden();
        $this->actingAs($this->user('manager'))->get('/admin/license')->assertForbidden();

        $this->actingAs($this->user('owner'));
        $res = $this->get('/admin/system')->assertOk()->assertSee('System health')->assertSee('PHP extensions')->assertSee('Scheduler')->assertSee('Sample data');
        $this->assertContains('Database', array_column($res->viewData('checks'), 'label'));
        $this->assertSame(config('nexcart.version'), $res->viewData('version'));
    }

    public function test_scheduler_heartbeat_shows_as_running(): void
    {
        $this->actingAs($this->user('owner'));
        $this->get('/admin/system')->assertSee('Never ran');

        Artisan::call('nexcart:heartbeat');
        $this->get('/admin/system')->assertSee('Running');

        Cache::forever('nexcart.heartbeat', now()->subMinutes(10)->timestamp);
        $this->get('/admin/system')->assertSee('Last ran 10 minutes ago');
    }

    public function test_update_notice_only_tells_it_never_installs(): void
    {
        $this->actingAs($this->user('owner'));
        $this->assertNull(UpdateChecker::latest());   // not configured → silent

        config(['nexcart.update_url' => 'https://updates.example.com/nexcart.json', 'nexcart.version' => '1.0.0']);
        $feed = ['version' => '1.2.0', 'notes' => 'Faster checkout', 'url' => 'https://dl.example.com/n.zip'];   // changed between steps, hence by reference
        Http::fake(function () use (&$feed) {
            return $feed === null ? throw new \RuntimeException('down') : Http::response($feed);
        });

        $this->get('/admin/system')->assertSee('NexCart 1.2.0 is available')->assertSee('Faster checkout')->assertSee('php artisan nexcart:update');

        Cache::flush();
        $feed = ['version' => '1.0.0'];
        $this->get('/admin/system')->assertDontSee('is available')->assertSee('You are on the latest version.');

        Cache::flush();
        $feed = ['version' => 'not-a-version', 'url' => 'javascript:alert(1)'];
        $this->assertNull(UpdateChecker::latest(true));

        Cache::flush();
        $feed = ['version' => '2.0.0', 'url' => 'javascript:alert(1)'];
        $this->assertNull(UpdateChecker::latest(true)['url']);   // only http(s) links are ever shown

        Cache::flush();
        $feed = null;
        $this->get('/admin/system')->assertOk()->assertSee('Could not reach the update server');
    }

    public function test_update_command_migrates_syncs_permissions_and_locks_the_installer(): void
    {
        $this->assertFalse(Installer::locked());
        $this->artisan('nexcart:update', ['--no-backup' => true])->expectsOutputToContain('Update finished')->assertSuccessful();
        $this->assertTrue(Installer::locked());   // sites installed before the web installer existed get locked
    }

    // ================= backups =================

    public function test_backup_zip_contains_database_uploads_and_manifest(): void
    {
        $this->scratchStorage();
        file_put_contents(storage_path('app/public/product.png'), 'img');
        File::ensureDirectoryExists(storage_path('app/private/media/1'));
        file_put_contents(storage_path('app/private/media/1/rx.jpg'), 'rx');
        $svc = app(BackupService::class);

        $path = $svc->create(true, $this->fileDb());
        $this->assertMatchesRegularExpression('/nexcart-\d{8}-\d{6}\.zip$/', $path);

        $zip = new ZipArchive;
        $zip->open($path);
        $names = array_map(fn ($i) => $zip->getNameIndex($i), range(0, $zip->numFiles - 1));
        $this->assertContains('database.sqlite', $names);
        $this->assertContains('manifest.json', $names);
        $this->assertContains('files/public/product.png', $names);
        $this->assertContains('files/private/media/1/rx.jpg', $names);
        $manifest = json_decode($zip->getFromName('manifest.json'), true);
        $this->assertSame(['NexCart', 'sqlite', true, 2], [$manifest['app'], $manifest['driver'], $manifest['with_uploads'], $manifest['files']]);
        $zip->close();

        $lean = $svc->create(false, $this->fileDb('bk2'));
        $z2 = new ZipArchive;
        $z2->open($lean);
        $this->assertFalse($z2->locateName('files/public/product.png'));
        $z2->close();
    }

    public function test_restore_brings_back_database_and_files(): void
    {
        $this->scratchStorage();
        file_put_contents(storage_path('app/public/logo.png'), 'original');
        $db = $this->fileDb();
        $svc = app(BackupService::class);
        $zip = $svc->create(true, $db);

        $db->table('notes')->insert(['body' => 'added later']);
        file_put_contents(storage_path('app/public/logo.png'), 'changed');
        $this->assertSame(2, $db->table('notes')->count());

        $info = $svc->restore($zip, $db);
        $db->reconnect();
        $this->assertSame(['hello'], $db->table('notes')->pluck('body')->all());
        $this->assertSame('original', file_get_contents(storage_path('app/public/logo.png')));
        $this->assertSame(config('nexcart.version'), $info['version']);
    }

    public function test_restore_rejects_foreign_files(): void
    {
        $this->scratchStorage();
        $bad = $this->tmp.'/other.zip';
        $z = new ZipArchive;
        $z->open($bad, ZipArchive::CREATE);
        $z->addFromString('manifest.json', json_encode(['app' => 'SomethingElse']));
        $z->close();

        $this->expectExceptionMessage('not a NexCart backup');
        app(BackupService::class)->restore($bad, $this->fileDb());
    }

    public function test_backups_are_pruned_to_the_newest_and_names_are_validated(): void
    {
        $this->scratchStorage();
        $svc = app(BackupService::class);
        foreach (range(1, 5) as $i) {
            $f = $svc->directory().sprintf('/nexcart-2026110%d-000000.zip', $i);
            file_put_contents($f, 'x');
            touch($f, time() - (10 - $i) * 100);
        }
        $this->assertSame(3, $svc->prune(2));
        $this->assertSame(['nexcart-20261105-000000.zip', 'nexcart-20261104-000000.zip'], array_column($svc->all(), 'name'));

        foreach (['../.env', 'nexcart-1.zip', 'x.zip', 'nexcart-20261105-000000.zip.php', 'nexcart-20261105-000000.sql'] as $bad) {
            $this->assertFalse(BackupService::validName($bad), $bad);
        }
        $this->expectExceptionMessage('No such backup');
        $svc->path('../../.env');
    }

    public function test_backup_page_download_delete_settings_and_failure_message(): void
    {
        $this->scratchStorage();
        $svc = app(BackupService::class);
        file_put_contents($file = $svc->directory().'/nexcart-20261105-101010.zip', 'zipdata');
        $this->actingAs($this->user('owner'));

        $this->get('/admin/backups')->assertOk()->assertSee('nexcart-20261105-101010.zip');
        $this->get('/admin/backups/nexcart-20261105-101010.zip')->assertOk()->assertDownload('nexcart-20261105-101010.zip');
        $this->get('/admin/backups/..%2F.env')->assertNotFound();
        $this->get('/admin/backups/nexcart-20990101-000000.zip')->assertNotFound();

        // the test database is in memory — it cannot be backed up, and the owner is told why (no crash)
        $this->post('/admin/backups')->assertSessionHas('error');

        $this->post('/admin/backups/settings', ['keep' => 0])->assertSessionHasErrors('keep');
        $this->post('/admin/backups/settings', ['keep' => 14, 'auto' => 1])->assertRedirect();
        $this->assertSame([true, 14], [(bool) setting('backup.auto'), (int) setting('backup.keep')]);

        $this->delete('/admin/backups/nexcart-20261105-101010.zip')->assertRedirect();
        $this->assertFileDoesNotExist($file);
    }

    public function test_backup_command_respects_the_scheduled_switch_and_reports_failure(): void
    {
        $this->scratchStorage();
        setting()->set('backup.auto', false);
        $this->assertSame(0, Artisan::call('nexcart:backup', ['--scheduled' => true]));
        $this->assertSame([], app(BackupService::class)->all());

        $this->assertSame(1, Artisan::call('nexcart:backup'));   // in-memory database
        $this->assertStringContainsString('Backup failed', Artisan::output());
    }

    public function test_mysql_dump_values_are_binary_safe_single_lines(): void
    {
        $sql = BackupService::insertStatement('orders', [
            ['id' => 1, 'name' => "O'Brien\n; DROP TABLE x;", 'note' => null, 'total' => 12.5, 'paid' => true, 'empty' => ''],
            ['id' => 2, 'name' => 'اردو', 'note' => 'x', 'total' => 3, 'paid' => false, 'empty' => ''],
        ]);

        $this->assertSame(1, substr_count($sql, "\n"));   // exactly one line, so restore can run it statement by statement
        $this->assertStringStartsWith('INSERT INTO `orders` (`id`,`name`,`note`,`total`,`paid`,`empty`) VALUES (1,CONVERT(0x', $sql);
        $this->assertStringContainsString('NULL,12.5,1,\'\')', $sql);
        $this->assertStringNotContainsString('DROP TABLE', $sql);                       // data is hex-encoded, never raw
        $this->assertStringContainsString(bin2hex('اردو'), $sql);
        $this->assertSame('', BackupService::insertStatement('t', []));
        $this->assertSame('0', BackupService::literal(0.0));
    }

    // ================= license keys =================

    private function keys(): array
    {
        $pair = sodium_crypto_sign_keypair();

        return ['public' => base64_encode(sodium_crypto_sign_publickey($pair)), 'private' => base64_encode(sodium_crypto_sign_secretkey($pair))];
    }

    private function licensed(array $overrides = []): array
    {
        $k = $this->keys();
        config(['license.public_key' => $k['public']]);
        License::reset();

        return $k + ['key' => License::issue($overrides + ['id' => 'L-1', 'licensee' => 'Fresh Burgers', 'domain' => '*', 'edition' => 'standard', 'expires' => null], $k['private'])];
    }

    public function test_licensing_is_off_without_a_public_key(): void
    {
        $this->assertFalse(License::enabled());
        $this->assertSame('disabled', License::status()['state']);
        $this->assertFalse(License::needsAttention());
        $this->assertFalse(License::showBranding());

        $this->actingAs($this->user('owner'))->get('/admin')->assertDontSee('not licensed');
        auth()->logout();
        $this->get('/')->assertDontSee('Powered by NexCart');
    }

    public function test_valid_license_verifies_and_hides_the_reminders(): void
    {
        $k = $this->licensed(['domain' => 'localhost']);
        $this->assertSame('missing', License::status(true)['state']);
        $this->assertTrue(License::needsAttention());

        setting()->set('system.license', $k['key']);
        $st = License::status(true);
        $this->assertSame(['valid', 'Fresh Burgers'], [$st['state'], $st['data']['licensee']]);
        $this->assertFalse(License::needsAttention());
        $this->assertFalse(License::showBranding());
    }

    public function test_license_problems_are_detected(): void
    {
        $k = $this->licensed();
        [$head, $body, $sig] = explode('.', $k['key']);

        // tampered payload, signature from someone else, garbage
        $forged = License::b64(json_encode(['licensee' => 'Thief', 'domain' => '*', 'expires' => null]));
        $this->assertNull(License::verify("NXC.{$forged}.{$sig}"));
        $other = $this->keys();
        $this->assertNull(License::verify(License::issue(['licensee' => 'X', 'domain' => '*'], $other['private'])));
        foreach (['', 'garbage', 'NXC.a.b', "NXC.{$body}.", $k['key'].'x'] as $bad) {
            $this->assertNull(License::verify($bad), $bad);
        }

        setting()->set('system.license', 'NXC.broken.broken');
        $this->assertSame('invalid', License::status(true)['state']);

        config(['app.url' => 'https://shop.example.com']);
        $wrong = License::issue(['licensee' => 'A', 'domain' => 'fresh.pk'], $k['private']);
        setting()->set('system.license', $wrong);
        $s = License::status(true);
        $this->assertSame('domain', $s['state']);
        $this->assertStringContainsString('fresh.pk', $s['reason']);

        $expired = License::issue(['licensee' => 'A', 'domain' => '*', 'expires' => now()->subDays(40)->toDateString()], $k['private']);
        setting()->set('system.license', $expired);
        $this->assertSame('expired', License::status(true)['state']);
        $this->assertTrue(License::showBranding());

        $grace = License::issue(['licensee' => 'A', 'domain' => '*', 'expires' => now()->subDays(3)->toDateString()], $k['private']);
        setting()->set('system.license', $grace);
        $this->assertSame('grace', License::status(true)['state']);
        $this->assertTrue(License::needsAttention());
        $this->assertFalse(License::showBranding());   // grace: reminder only
    }

    public function test_domain_patterns(): void
    {
        $this->assertTrue(License::domainMatches('fresh.pk', 'fresh.pk'));
        $this->assertTrue(License::domainMatches('fresh.pk', 'www.fresh.pk'));
        $this->assertFalse(License::domainMatches('fresh.pk', 'evil.com'));
        $this->assertFalse(License::domainMatches('fresh.pk', 'notfresh.pk'));
        $this->assertTrue(License::domainMatches('*.fresh.pk', 'shop.fresh.pk'));
        $this->assertTrue(License::domainMatches('*.fresh.pk', 'fresh.pk'));
        $this->assertFalse(License::domainMatches('*.fresh.pk', 'fresh.pk.evil.com'));
        $this->assertTrue(License::domainMatches('*', 'anything.com'));
        foreach (['localhost', '127.0.0.1', 'staging.test', 'x.localhost'] as $dev) {
            $this->assertTrue(License::domainMatches('fresh.pk', $dev), $dev);
        }
    }

    public function test_owner_activates_and_removes_a_license_in_the_admin(): void
    {
        $k = $this->licensed(['domain' => 'localhost']);
        $this->actingAs($this->user('owner'));

        $this->get('/admin')->assertSee('This copy of NexCart is not licensed.');
        $this->get('/admin/license')->assertOk()->assertSee('No license');

        $this->post('/admin/license', ['key' => 'NXC.nonsense.nonsense'])->assertSessionHasErrors('key');
        $this->assertNull(setting('system.license'));

        $this->post('/admin/license', ['key' => $k['key']])->assertRedirect('/admin/license')->assertSessionHas('success');
        $this->get('/admin/license')->assertSee('Licensed')->assertSee('Fresh Burgers')->assertSee('L-1')->assertSee('Lifetime');
        $this->get('/admin')->assertDontSee('This copy of NexCart is not licensed.');
        auth()->logout();
        $this->get('/')->assertDontSee('Powered by NexCart');

        $this->actingAs($this->user('owner'))->delete('/admin/license')->assertRedirect();
        $this->assertNull(setting('system.license'));
        auth()->logout();
        $this->get('/')->assertSee('Powered by NexCart');
    }

    public function test_vendor_commands_issue_keys_that_verify(): void
    {
        $out = $this->tmp.'/keys';
        $this->assertSame(0, Artisan::call('nexcart:license:keys', ['--out' => $out]));
        $this->assertFileExists($out.'/license-private.key');
        $this->assertSame('0600', substr(sprintf('%o', fileperms($out.'/license-private.key')), -4));

        config(['license.public_key' => trim(file_get_contents($out.'/license-public.key'))]);
        $this->assertSame(0, Artisan::call('nexcart:license:generate', ['licensee' => 'Acme', '--domain' => ['acme.pk', '*.acme.pk'], '--email' => 'a@acme.pk', '--expires' => '2099-12-31', '--private-key' => $out.'/license-private.key']));
        preg_match_all('/NXC\.[A-Za-z0-9_.-]+/', Artisan::output(), $m);
        $this->assertCount(2, $m[0]);
        $data = License::verify($m[0][0]);
        $this->assertSame(['Acme', 'acme.pk', '2099-12-31', 'standard'], [$data['licensee'], $data['domain'], $data['expires'], $data['edition']]);

        $this->assertSame(1, Artisan::call('nexcart:license:generate', ['licensee' => 'X', '--private-key' => $out.'/missing.key']));
        $this->assertSame(1, Artisan::call('nexcart:license:generate', ['licensee' => 'X', '--expires' => 'soon', '--private-key' => $out.'/license-private.key']));
    }

    // ================= installer =================

    public function test_requirements_report_the_environment(): void
    {
        $installer = new Installer;
        $rows = $installer->requirements();
        $this->assertSame('PHP 8.3+', $rows[0]['label']);
        $this->assertTrue($rows[0]['ok']);
        $this->assertContains('PHP extension: gd', array_column($rows, 'label'));
        $this->assertTrue($installer->requirementsMet());
        $this->assertTrue(collect($rows)->where('required', false)->isNotEmpty());   // optional rows (sodium) are listed but never block
    }

    public function test_env_writer_updates_in_place_and_quotes_safely(): void
    {
        $file = $this->tmp.'/.env';
        file_put_contents($file, "APP_NAME=NexCart\nDB_HOST=127.0.0.1\n# keep me\nDB_PASSWORD=old\n");

        EnvWriter::set($file, ['DB_HOST' => 'db.internal', 'DB_PASSWORD' => 'p@ss w"ord#1$x\\y', 'NEW_KEY' => 'a b']);
        $text = file_get_contents($file);

        $this->assertStringContainsString("APP_NAME=NexCart\n", $text);
        $this->assertStringContainsString("# keep me\n", $text);
        $this->assertStringContainsString("DB_HOST=db.internal\n", $text);
        $this->assertStringContainsString('NEW_KEY="a b"', $text);
        $this->assertSame(1, substr_count($text, 'DB_PASSWORD='));

        // what we wrote is what dotenv reads back
        $parsed = Dotenv::parse($text);
        $this->assertSame('p@ss w"ord#1$x\\y', $parsed['DB_PASSWORD']);
        $this->assertSame('db.internal', $parsed['DB_HOST']);

        $this->expectException(\InvalidArgumentException::class);
        EnvWriter::set($file, ['bad key' => 'x']);
    }

    public function test_first_run_creates_env_and_a_key_once(): void
    {
        file_put_contents($this->tmp.'/.env.example', "APP_NAME=NexCart\nAPP_KEY=\nDB_HOST=127.0.0.1\n");
        $old = [getenv('APP_KEY'), $_ENV['APP_KEY'] ?? null, $_SERVER['APP_KEY'] ?? null];

        FirstRun::ensureEnvironment($this->tmp);
        $env = file_get_contents($this->tmp.'/.env');
        preg_match('/^APP_KEY=(base64:[A-Za-z0-9+\/=]{43,})$/m', $env, $m);
        $this->assertNotEmpty($m, 'a key was generated');
        $this->assertStringContainsString('DB_HOST=127.0.0.1', $env);

        FirstRun::ensureEnvironment($this->tmp);   // never regenerates an existing key
        $this->assertSame($env, file_get_contents($this->tmp.'/.env'));

        $old[0] === false ? putenv('APP_KEY') : putenv('APP_KEY='.$old[0]);
        $old[1] === null ? $_ENV = array_diff_key($_ENV, ['APP_KEY' => 1]) : $_ENV['APP_KEY'] = $old[1];
        $old[2] === null ? $_SERVER = array_diff_key($_SERVER, ['APP_KEY' => 1]) : $_SERVER['APP_KEY'] = $old[2];
    }

    public function test_installer_is_unavailable_on_a_working_or_locked_site(): void
    {
        $this->assertFalse((new Installer)->available());          // database already has its tables
        $this->get('/install')->assertRedirect('/');                 // installed → home

        setting()->set('system.installed', false);
        $this->get('/install')->assertRedirect('/setup');            // tables exist, store not set up yet
        $this->post('/install', ['driver' => 'sqlite'])->assertNotFound();
    }

    public function test_visitors_go_to_the_installer_when_the_database_is_missing_and_never_after_install(): void
    {
        setting()->set('system.installed', false);
        $this->app->instance(Installer::class, new class extends Installer
        {
            public function needsDatabase(): bool
            {
                return true;
            }
        });

        $this->get('/')->assertRedirect('/install');
        $this->get('/install')->assertOk()->assertSee('Server check')->assertSee('Connect and create tables');

        // an installed site whose database goes away must NOT offer to be re-pointed at another database
        Installer::lock();
        $this->get('/')->assertStatus(503)->assertSee('be right back');
        $this->get('/install')->assertRedirect('/login');
        $this->post('/install', ['driver' => 'sqlite'])->assertNotFound();
    }

    // ================= demo data =================

    public function test_demo_data_for_every_business_type_and_clean_removal(): void
    {
        Storage::fake('public');
        $demo = app(DemoData::class);

        foreach (['general', 'fastfood', 'pharmacy', 'medical_store'] as $type) {
            BusinessProfile::apply($type);
            $this->assertFalse($demo->loaded());
            $n = $demo->load($type);
            $this->assertGreaterThanOrEqual(7, $n, $type);
            $this->assertSame($n, Product::where('sku', 'like', 'DEMO-%')->count());
            $this->assertTrue(Product::where('sku', 'like', 'DEMO-%')->get()->every(fn ($p) => $p->status === 'active' && $p->getMedia('images')->count() === 1 && $p->totalStock() > 0), "$type products are sellable and have a picture");

            // a real product the owner adds must survive removal
            $mine = new Product;
            $mine->forceFill(['name' => "Mine {$type}", 'price' => 10, 'status' => 'active', 'category_id' => Category::first()->id])->save();

            $this->assertSame($n, $demo->remove());
            $this->assertFalse($demo->loaded());
            $this->assertSame(0, Product::where('sku', 'like', 'DEMO-%')->count());
            $this->assertNotNull(Product::find($mine->id));
            $this->assertNotNull(Category::find($mine->category_id), 'a category that real products use stays');
            $mine->forceDelete();
            Category::query()->delete();
            Brand::query()->delete();
        }
    }

    public function test_pharmacy_demo_has_prescriptions_batches_and_expiring_stock(): void
    {
        Storage::fake('public');
        BusinessProfile::apply('pharmacy');
        app(DemoData::class)->load('pharmacy');

        $rx = Product::where('requires_prescription', true)->get();
        $this->assertGreaterThanOrEqual(2, $rx->count());
        $this->assertSame('Amoxicillin 500mg (12 capsules)', $rx->first()->name);
        $this->assertGreaterThan(5, ProductBatch::count());
        $this->assertTrue(ProductBatch::where('expires_at', '<=', now()->addDays(60))->exists());   // something to show in "expiring soon"
        $this->assertNotEmpty(Product::where('name', 'like', 'Panadol%')->first()->specs['composition']);
        $this->assertTrue(Product::where('name', 'like', 'Augmentin%')->first()->isLowStock());
    }

    public function test_fastfood_demo_has_add_ons_and_variants(): void
    {
        Storage::fake('public');
        BusinessProfile::apply('fastfood');
        app(DemoData::class)->load('fastfood');

        $burger = Product::where('name', 'Zinger Burger')->firstOrFail();
        $this->assertSame(['Extras'], $burger->modifierGroups->pluck('name')->all());
        $this->assertSame(['Hot', 12], [$burger->specs['spice_level'], $burger->specs['prep_time_min']]);
        $pizza = Product::where('name', 'Margherita Pizza')->firstOrFail();
        $this->assertTrue($pizza->isVariable());
        $this->assertCount(3, $pizza->variants);
        $this->assertSame('650.00', (string) $pizza->price);   // "from" price follows the cheapest variant
        $this->get('/shop')->assertOk()->assertSee('Zinger Burger');
    }

    public function test_demo_data_cannot_be_loaded_twice_and_is_owner_only(): void
    {
        Storage::fake('public');
        $this->actingAs($this->user('manager'))->post('/admin/system/demo')->assertForbidden();

        $this->actingAs($this->user('owner'));
        $this->post('/admin/system/demo')->assertRedirect()->assertSessionHas('success');
        $count = Product::count();
        $this->assertGreaterThan(0, $count);
        $this->post('/admin/system/demo')->assertSessionHas('error');
        $this->assertSame($count, Product::count());

        $this->get('/admin/system')->assertSee('Remove sample data');
        $this->delete('/admin/system/demo')->assertRedirect()->assertSessionHas('success');
        $this->assertSame(0, Product::count());
    }

    public function test_demo_command_and_setup_wizard_option(): void
    {
        Storage::fake('public');
        $this->assertSame(1, Artisan::call('nexcart:demo', ['--type' => 'bogus']));
        $this->assertSame(0, Artisan::call('nexcart:demo', ['--type' => 'medical_store']));
        $this->assertStringContainsString('sample product(s) added for medical_store', Artisan::output());
        $this->assertSame(1, Artisan::call('nexcart:demo'));   // already loaded
        $this->assertSame(0, Artisan::call('nexcart:demo', ['--remove' => true]));
        $this->assertSame(0, Product::count());

        setting()->set('system.installed', false);
        setting()->set('demo.ids', null);
        $this->post('/setup', ['business_type' => 'fastfood', 'store_name' => 'Hub', 'currency' => 'PKR', 'currency_symbol' => 'Rs', 'timezone' => 'Asia/Karachi', 'name' => 'Owner', 'email' => 'o@example.com', 'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1', 'sample_data' => 1])->assertRedirect();
        $this->assertGreaterThanOrEqual(7, Product::count());
        $this->assertTrue(Installer::locked());   // the wizard locks the installer when it finishes
    }
}
