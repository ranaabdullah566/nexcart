<?php

namespace Tests\Feature;

use App\Mail\ResetPasswordMail;
use App\Models\User;
use App\Support\Totp;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
        Mail::fake();
    }

    private function token(User $u): string
    {
        return Password::createToken($u);
    }

    public function test_pages_render_and_login_links_to_them(): void
    {
        $this->get('/login')->assertSee('/forgot-password', false)->assertSee('Forgot password?');
        $this->get('/forgot-password')->assertOk()->assertSee('name="nx_ts"', false)->assertSee('Send reset link');
        $this->get('/reset-password/abc?email=a@b.test')->assertOk()->assertSee('value="a@b.test"', false)->assertSee('name="token"', false);
    }

    public function test_link_is_mailed_to_a_known_account_and_the_page_reveals_nothing(): void
    {
        $u = User::factory()->create(['email' => 'sara@example.com', 'name' => 'Sara']);

        $known = $this->post('/forgot-password', ['email' => 'Sara@Example.com']);
        $unknown = $this->post('/forgot-password', ['email' => 'nobody@example.com']);

        $this->assertSame(session('success'), $known->getSession()->get('success'));
        $this->assertSame($known->getSession()->get('success'), $unknown->getSession()->get('success'));   // same words either way
        Mail::assertSent(ResetPasswordMail::class, 1);
        Mail::assertSent(ResetPasswordMail::class, fn ($m) => $m->hasTo('sara@example.com') && str_contains($m->url(), '/reset-password/') && str_contains($m->url(), 'email=sara%40example.com'));
        $this->assertDatabaseHas('activity_log', ['event' => 'auth.reset_requested']);
    }

    public function test_requests_per_address_are_capped(): void
    {
        User::factory()->create(['email' => 'cap@example.com']);
        Mail::fake();
        for ($i = 0; $i < 5; $i++) {
            DB::table('password_reset_tokens')->delete();   // skip the broker's own 60 s wait
            $this->post('/forgot-password', ['email' => 'cap@example.com'])->assertSessionHas('success');
        }
        Mail::assertSent(ResetPasswordMail::class, 3);
        RateLimiter::clear('pw-reset:cap@example.com');
    }

    public function test_reset_changes_the_password_once_and_clears_lockout(): void
    {
        $u = User::factory()->create(['email' => 'amir@example.com', 'password' => 'Old-pass-12345']);
        $token = $this->token($u);
        RateLimiter::hit('login:amir@example.com|127.0.0.1', 900);

        $this->post('/reset-password', ['token' => $token, 'email' => 'amir@example.com', 'password' => 'Brand-new-pass-9', 'password_confirmation' => 'Brand-new-pass-9'])
            ->assertRedirect('/login')->assertSessionHas('success');
        $this->assertTrue(Hash::check('Brand-new-pass-9', $u->fresh()->password));
        $this->assertSame(0, RateLimiter::attempts('login:amir@example.com|127.0.0.1'));
        $this->assertDatabaseHas('activity_log', ['event' => 'auth.password_reset']);

        // the link cannot be used twice
        $this->post('/reset-password', ['token' => $token, 'email' => 'amir@example.com', 'password' => 'Another-pass-77', 'password_confirmation' => 'Another-pass-77'])->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('Brand-new-pass-9', $u->fresh()->password));

        $this->post('/login', ['email' => 'amir@example.com', 'password' => 'Brand-new-pass-9'])->assertRedirect();
        $this->assertAuthenticated();
    }

    public function test_bad_token_weak_password_and_deleted_accounts_are_refused(): void
    {
        $u = User::factory()->create(['email' => 'lee@example.com', 'password' => 'Old-pass-12345']);
        $good = $this->token($u);

        $this->post('/reset-password', ['token' => 'wrong', 'email' => 'lee@example.com', 'password' => 'Brand-new-pass-9', 'password_confirmation' => 'Brand-new-pass-9'])->assertSessionHasErrors('email');
        $this->post('/reset-password', ['token' => $good, 'email' => 'lee@example.com', 'password' => 'short1', 'password_confirmation' => 'short1'])->assertSessionHasErrors('password');
        $this->post('/reset-password', ['token' => $good, 'email' => 'lee@example.com', 'password' => 'Brand-new-pass-9', 'password_confirmation' => 'mismatch'])->assertSessionHasErrors('password');
        $this->assertTrue(Hash::check('Old-pass-12345', $u->fresh()->password));

        $u->delete();   // soft-deleted: no reset, no e-mail
        $this->post('/forgot-password', ['email' => 'lee@example.com']);
        Mail::assertNothingSent();
        $this->post('/reset-password', ['token' => $good, 'email' => 'lee@example.com', 'password' => 'Brand-new-pass-9', 'password_confirmation' => 'Brand-new-pass-9'])->assertSessionHasErrors('email');
    }

    public function test_resetting_does_not_bypass_two_factor_for_staff(): void
    {
        $secret = Totp::secret();
        $owner = tap(User::factory()->create(['email' => 'boss@example.com', 'two_factor_secret' => $secret, 'two_factor_recovery_codes' => ['aaaa-bbbb'], 'two_factor_confirmed_at' => now()]), fn ($u) => $u->assignRole('owner'));
        $token = $this->token($owner);

        $this->post('/reset-password', ['token' => $token, 'email' => 'boss@example.com', 'password' => 'Brand-new-pass-9', 'password_confirmation' => 'Brand-new-pass-9'])->assertRedirect('/login');
        $this->post('/login', ['email' => 'boss@example.com', 'password' => 'Brand-new-pass-9'])->assertRedirect('/login/two-factor');
        $this->assertGuest();   // the new password alone still is not enough
    }
}
