<?php

namespace Tests\Feature;

use App\Support\Installer;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\TestCase;

/**
 * Applying the installer re-points the default database connection, so it runs in its own PHP process
 * and cannot disturb the in-memory database the other tests share.
 */
class InstallerApplyTest extends TestCase
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_installer_validates_input_and_installs_to_a_fresh_database(): void
    {
        $tmp = sys_get_temp_dir().'/nexcart-inst-'.bin2hex(random_bytes(4));
        File::ensureDirectoryExists($tmp);
        $envFile = $tmp.'/.env';
        file_put_contents($envFile, "APP_NAME=NexCart\nAPP_KEY=base64:abc\n");
        config(['nexcart.env_path' => $envFile, 'database.connections.sqlite.database' => ':memory:']);

        $this->app->instance(Installer::class, new class extends Installer
        {
            public function needsDatabase(): bool
            {
                return true;
            }
        });

        // nothing is set up yet, so the sessions table does not exist either: the installer must work on file sessions
        config(['session.driver' => 'file', 'cache.default' => 'array']);

        $this->post('/install', ['driver' => 'mysql', 'host' => 'bad host!', 'port' => 3306, 'database' => 'x', 'username' => 'u'])->assertSessionHasErrors('host');
        $this->post('/install', ['driver' => 'mysql', 'host' => 'db', 'port' => 99999, 'database' => 'x', 'username' => 'u'])->assertSessionHasErrors('port');
        $this->post('/install', ['driver' => 'mysql', 'host' => 'db', 'port' => 3306, 'database' => 'x; drop', 'username' => 'u'])->assertSessionHasErrors('database');
        $this->post('/install', ['driver' => 'oracle'])->assertSessionHasErrors('driver');
        $this->post('/install', ['driver' => 'mysql', 'host' => '127.0.0.1', 'port' => 1, 'database' => 'x', 'username' => 'u', 'password' => 'p'])->assertSessionHasErrors('host');   // nothing listens on port 1
        $this->assertStringNotContainsString('DB_CONNECTION', file_get_contents($envFile));   // a failed attempt writes nothing

        $db = $tmp.'/fresh.sqlite';
        $installer = new Installer;
        $this->assertSame(['ok' => true, 'error' => null], $installer->testConnection(['driver' => 'sqlite', 'database' => $db]));
        $installer->apply(['driver' => 'sqlite', 'database' => $db], 'https://shop.example.com');

        $env = file_get_contents($envFile);
        $this->assertStringContainsString('APP_KEY=base64:abc', $env);   // untouched
        $this->assertStringContainsString('DB_CONNECTION=sqlite', $env);
        $this->assertStringContainsString("DB_DATABASE={$db}", $env);
        $this->assertStringContainsString('APP_URL=https://shop.example.com', $env);
        $this->assertStringContainsString('SESSION_DRIVER=file', $env);
        $this->assertTrue(Schema::hasTable('settings'));   // the migrations ran on the new connection
        $this->assertTrue(Schema::hasTable('orders'));

        File::deleteDirectory($tmp);
    }
}
