<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Totp;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
        RateLimiter::clear('x');
    }

    private function owner(array $extra = []): User
    {
        return tap(User::factory()->create(['password' => 'Str0ng-pass-99'] + $extra), fn ($u) => $u->assignRole('owner'));
    }

    public function test_totp_matches_rfc_6238_vector(): void
    {
        // RFC 6238 SHA-1 secret "12345678901234567890" (base32 GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ), T=59 -> 94287082 (last 6 digits)
        $this->assertSame('287082', Totp::code('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 59));
        $this->assertTrue(Totp::verify('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', '287082', 59));
        $this->assertFalse(Totp::verify('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', '000000', 59));
    }

    public function test_login_locks_out_after_repeated_failures(): void
    {
        $u = $this->owner();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $u->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        }
        $this->post('/login', ['email' => $u->email, 'password' => 'Str0ng-pass-99'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseHas('activity_log', ['event' => 'auth.failed']);
    }

    public function test_two_factor_login_flow_and_recovery_code(): void
    {
        $secret = Totp::secret();
        $u = $this->owner(['two_factor_secret' => $secret, 'two_factor_recovery_codes' => ['aaaa-bbbb'], 'two_factor_confirmed_at' => now()]);

        $this->post('/login', ['email' => $u->email, 'password' => 'Str0ng-pass-99'])->assertRedirect('/login/two-factor');
        $this->assertGuest();

        $this->post('/login/two-factor', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();

        $this->post('/login/two-factor', ['code' => Totp::code($secret)])->assertRedirect();
        $this->assertAuthenticatedAs($u);

        $this->post('/logout');
        $this->post('/login', ['email' => $u->email, 'password' => 'Str0ng-pass-99']);
        $this->post('/login/two-factor', ['code' => 'aaaa-bbbb'])->assertRedirect();
        $this->assertAuthenticated();
        $this->assertEmpty($u->fresh()->two_factor_recovery_codes);   // single use
    }

    public function test_enabling_two_factor_from_security_page(): void
    {
        $u = $this->owner();
        $this->actingAs($u)->post('/admin/security/two-factor')->assertRedirect();
        $secret = session('2fa.setup');
        $this->get('/admin/security')->assertOk()->assertSee('<svg', false);
        $this->post('/admin/security/two-factor/confirm', ['code' => '111111'])->assertSessionHasErrors('code');
        $this->post('/admin/security/two-factor/confirm', ['code' => Totp::code($secret)])->assertRedirect();
        $this->assertNotNull($u->fresh()->two_factor_confirmed_at);
        $this->assertCount(10, $u->fresh()->two_factor_recovery_codes);
        $this->assertNotSame($secret, $u->getRawOriginal('two_factor_secret'));   // stored encrypted
    }

    public function test_password_policy_and_change_requires_current_password(): void
    {
        $u = $this->owner();
        $this->actingAs($u)->put('/admin/security/password', ['current_password' => 'nope', 'password' => 'Newpass12345', 'password_confirmation' => 'Newpass12345'])->assertSessionHasErrors('current_password');
        $this->put('/admin/security/password', ['current_password' => 'Str0ng-pass-99', 'password' => 'short1', 'password_confirmation' => 'short1'])->assertSessionHasErrors('password');
        $this->put('/admin/security/password', ['current_password' => 'Str0ng-pass-99', 'password' => 'Newpass12345', 'password_confirmation' => 'Newpass12345'])->assertSessionHasNoErrors();
    }

    public function test_require_two_factor_policy_blocks_admin_until_enabled(): void
    {
        setting()->set('security.require_2fa', true);
        $this->actingAs($this->owner())->get('/admin')->assertRedirect('/admin/security');
        $this->get('/admin/security')->assertOk();
    }

    public function test_idle_session_is_signed_out(): void
    {
        $this->actingAs($this->owner())->withSession(['nx_last_seen' => time() - 3 * 3600])->get('/admin')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_scanner_probes_are_blocked_then_banned(): void
    {
        $this->get('/.env')->assertNotFound();
        $this->get('/wp-login.php')->assertNotFound();
        $this->get('/.git/config')->assertNotFound();
        $this->get('/phpmyadmin')->assertNotFound();
        $this->get('/xmlrpc.php')->assertNotFound();
        $this->get('/')->assertForbidden();   // 5 strikes: IP banned
        Cache::flush();
        $this->get('/')->assertOk();
    }

    public function test_security_headers_and_csp_nonce(): void
    {
        $r = $this->get('/login')->assertOk();
        $csp = $r->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $csp);
        preg_match("/'nonce-([^']+)'/", $csp, $m);
        $this->assertStringContainsString('nonce="'.$m[1].'"', $r->getContent());
        $this->assertSame('same-origin', $r->headers->get('Cross-Origin-Opener-Policy'));
    }

    public function test_honeypot_blocks_bots_and_lets_real_form_through(): void
    {
        $u = User::factory()->create(['password' => 'Str0ng-pass-99']);
        $h = ['X-Enforce-Honeypot' => '1'];
        $creds = ['email' => $u->email, 'password' => 'Str0ng-pass-99'];

        $this->withHeaders($h)->post('/login', $creds)->assertSessionHas('error');                                   // no timestamp: never rendered
        $fresh = Crypt::encryptString((string) time());
        $this->withHeaders($h)->post('/login', $creds + ['nx_ts' => $fresh])->assertSessionHas('error');             // too fast
        $old = Crypt::encryptString((string) (time() - 10));
        $this->withHeaders($h)->post('/login', $creds + ['nx_ts' => $old, 'nx_website' => 'http://spam'])->assertSessionHas('error');   // trap filled
        $this->assertGuest();
        $this->withHeaders($h)->post('/login', $creds + ['nx_ts' => $old])->assertRedirect();
        $this->assertAuthenticated();
        $this->get('/login')->assertDontSee('nx_ts');   // (logged in -> redirected; real render checked below)
    }

    public function test_forms_render_the_honeypot_and_account_changes_need_current_password(): void
    {
        auth()->logout();
        $this->get('/register')->assertSee('name="nx_ts"', false)->assertSee('nx_website', false);
        $u = User::factory()->create(['password' => 'Str0ng-pass-99', 'email' => 'me@x.test']);
        $base = ['name' => 'Me', 'email' => 'me@x.test'];
        $this->actingAs($u)->put('/account', $base + ['password' => 'Another-pass-77', 'password_confirmation' => 'Another-pass-77'])->assertSessionHasErrors('current_password');
        $this->put('/account', ['email' => 'new@x.test'] + $base)->assertSessionHasErrors('current_password');
        $this->put('/account', ['email' => 'new@x.test', 'current_password' => 'Str0ng-pass-99'] + $base)->assertSessionHasNoErrors();
        $this->assertSame('new@x.test', $u->fresh()->email);
        $this->put('/account', ['name' => 'Renamed', 'email' => 'new@x.test'])->assertSessionHasNoErrors();   // harmless edits need nothing
    }

    public function test_sensitive_pages_are_not_cacheable_or_indexable(): void
    {
        $r = $this->actingAs($this->owner())->get('/admin/security');
        $this->assertStringContainsString('no-store', $r->headers->get('Cache-Control'));
        $this->assertStringContainsString('noindex', $r->headers->get('X-Robots-Tag'));
    }

    public function test_server_errors_are_handed_to_the_page_for_inline_display_and_validator_script_loads(): void
    {
        $r = $this->from('/register')->followingRedirects()->post('/register', ['name' => '', 'email' => 'nope', 'password' => 'x', 'password_confirmation' => 'y']);
        $r->assertOk()->assertSee('name="nx-errors"', false)->assertSee('nx-validate.js', false)->assertSee('name="nx-vmsg"', false);
        $this->assertStringContainsString('The name field is required.', html_entity_decode($r->getContent()));
    }

    public function test_sign_in_and_sign_up_use_the_store_layout_and_keep_their_protections(): void
    {
        $this->get('/login')->assertOk()->assertSee('mk-auth', false)->assertSee('mk-header', false)->assertSee('name="nx_ts"', false)->assertSee('data-pw-toggle', false);
        $this->get('/register')->assertOk()->assertSee('id="pwRules"', false)->assertSee('minlength="10"', false)->assertSee('name="nx_website"', false);
        $this->get('/login/two-factor')->assertRedirect('/login');
    }
}
