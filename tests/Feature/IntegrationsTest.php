<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Integrations;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialUser;
use Tests\TestCase;

class IntegrationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
    }

    private function enableCaptcha(): void
    {
        setting()->set('integrations.recaptcha_enabled', true);
        setting()->set('integrations.recaptcha_site', 'site-key');
        Integrations::setSecret('recaptcha_secret', 'secret-key');
    }

    public function test_owner_saves_keys_and_secrets_are_encrypted_and_kept_when_blank(): void
    {
        $owner = tap(User::factory()->create(), fn ($u) => $u->assignRole('owner'));
        $this->actingAs($owner)->post('/admin/security/integrations', ['recaptcha_enabled' => 1, 'recaptcha_site' => 'S', 'recaptcha_secret' => 'TOPSECRET', 'google_enabled' => 1, 'google_client_id' => 'cid', 'google_client_secret' => 'gsec'])->assertRedirect();
        $this->assertNotSame('TOPSECRET', setting('integrations.recaptcha_secret'));
        $this->assertSame('TOPSECRET', Integrations::secret('recaptcha_secret'));
        $this->post('/admin/security/integrations', ['recaptcha_enabled' => 1, 'recaptcha_site' => 'S', 'recaptcha_secret' => '', 'google_enabled' => 1, 'google_client_id' => 'cid'])->assertRedirect();
        $this->assertSame('TOPSECRET', Integrations::secret('recaptcha_secret'));
        $this->get('/admin/security')->assertDontSee('TOPSECRET');
        $this->assertTrue(Integrations::recaptcha() && Integrations::google());
    }

    public function test_captcha_blocks_login_without_valid_token_and_passes_with_one(): void
    {
        $this->enableCaptcha();
        $u = User::factory()->create(['password' => 'Str0ng-pass-99']);
        $this->get('/login')->assertSee('g-recaptcha', false)->assertSee('site-key', false);
        $this->assertStringContainsString('https://www.google.com/recaptcha/', $this->get('/login')->headers->get('Content-Security-Policy'));

        $this->post('/login', ['email' => $u->email, 'password' => 'Str0ng-pass-99'])->assertSessionHasErrors('g-recaptcha-response');
        $this->assertGuest();

        $ok = false;
        Http::fake(['www.google.com/*' => function () use (&$ok) { return Http::response(['success' => $ok]); }]);
        $this->post('/login', ['email' => $u->email, 'password' => 'Str0ng-pass-99', 'g-recaptcha-response' => 'bad'])->assertSessionHasErrors('g-recaptcha-response');

        $ok = true;
        $this->post('/login', ['email' => $u->email, 'password' => 'Str0ng-pass-99', 'g-recaptcha-response' => 'good'])->assertRedirect();
        $this->assertAuthenticated();
    }

    public function test_captcha_fails_closed_when_google_unreachable(): void
    {
        $this->enableCaptcha();
        Http::fake(['www.google.com/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('down')]);
        $this->post('/register', ['name' => 'A', 'email' => 'a@x.test', 'password' => 'Str0ng-pass-99', 'password_confirmation' => 'Str0ng-pass-99', 'g-recaptcha-response' => 'x'])->assertSessionHasErrors('g-recaptcha-response');
        $this->assertDatabaseMissing('users', ['email' => 'a@x.test']);
    }

    public function test_no_captcha_when_disabled(): void
    {
        $this->get('/login')->assertDontSee('g-recaptcha', false)->assertDontSee('Continue with Google');
    }

    private function google(string $id, string $email): void
    {
        setting()->set('integrations.google_enabled', true);
        setting()->set('integrations.google_client_id', 'cid');
        Integrations::setSecret('google_client_secret', 'sec');
        $su = (new SocialUser)->map(['id' => $id, 'name' => 'Gina', 'email' => $email]);
        $su->user = ['email_verified' => true];
        $driver = \Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
        $driver->shouldReceive('user')->andReturn($su);
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);
    }

    public function test_google_creates_a_customer_and_logs_in(): void
    {
        $this->google('g-1', 'Gina@Example.com');
        $this->get('/auth/google/callback')->assertRedirect(route('store.account'));
        $u = User::where('email', 'gina@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($u);
        $this->assertSame('g-1', $u->google_id);
        $this->assertFalse($u->isStaff());
    }

    public function test_google_cannot_sign_into_a_staff_account(): void
    {
        $owner = tap(User::factory()->create(['email' => 'boss@example.com']), fn ($u) => $u->assignRole('owner'));
        $this->google('g-2', 'boss@example.com');
        $this->get('/auth/google/callback')->assertRedirect('/login')->assertSessionHas('error');
        $this->assertGuest();
        $this->assertNull($owner->fresh()->google_id);
    }

    public function test_google_routes_404_when_off(): void
    {
        $this->get('/auth/google')->assertNotFound();
    }

    public function test_prescription_upload_form_is_protected_by_recaptcha_when_enabled(): void
    {
        \Illuminate\Support\Facades\Http::fake(['www.google.com/*' => \Illuminate\Support\Facades\Http::response(['success' => false])]);
        setting()->set('integrations.recaptcha_enabled', true);
        setting()->set('integrations.recaptcha_site', 'site-key');
        Integrations::setSecret('recaptcha_secret', 'secret-key');
        $form = ['name' => 'Sara', 'phone' => '03001234567', 'files' => [\Illuminate\Http\UploadedFile::fake()->image('rx.jpg')]];

        $this->post('/upload-prescription', $form)->assertSessionHasErrors('g-recaptcha-response');
        $this->post('/upload-prescription', $form + ['g-recaptcha-response' => 'bad'])->assertSessionHasErrors('g-recaptcha-response');
    }
}
