<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Every public form: backend validation, markup rejection, input cleaning, bot traps and rate limits. */
class FormSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
        Mail::fake();
    }

    public function test_markup_and_script_are_rejected_in_text_fields(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $base = ['name' => 'Sara', 'email' => 'a@example.com', 'message' => 'Hello, I need help with my order.'];
        foreach (['<script>alert(1)</script>', '<img src=x onerror=alert(1)>', 'javascript:alert(1)', '&#60;b&#62;', '<b>bold</b>'] as $bad) {
            $this->post('/contact', array_merge($base, ['name' => $bad]))->assertSessionHasErrors('name');
            $this->post('/contact', array_merge($base, ['message' => 'hello there '.$bad]))->assertSessionHasErrors('message');
        }
        $this->assertSame(0, ContactMessage::count());

        // ordinary punctuation is fine
        $this->post('/contact', array_merge($base, ['name' => "Dr. O'Brien-Khan", 'message' => 'I love this <3 shop, 5 > 3 & 2 < 4!']))->assertSessionDoesntHaveErrors();
        $this->assertSame(1, ContactMessage::count());
    }

    public function test_control_characters_and_padding_are_stripped_before_validation(): void
    {
        $this->post('/contact', ['name' => "  Sara\0\x07 Khan  ", 'email' => 'a@example.com', 'message' => "Hello\0 there, need help please"])->assertSessionDoesntHaveErrors();
        $m = ContactMessage::firstOrFail();
        $this->assertSame('Sara Khan', $m->name);
        $this->assertStringNotContainsString("\0", $m->message);
    }

    public function test_lengths_types_and_formats_are_enforced_on_the_server(): void
    {
        $this->post('/contact', ['name' => str_repeat('a', 121), 'email' => 'not-an-email', 'phone' => 'abc', 'message' => 'short'])
            ->assertSessionHasErrors(['name', 'email', 'phone', 'message']);
        $this->post('/contact', ['name' => ['x'], 'email' => ['a@b.co'], 'message' => 'Hello there friend'])->assertSessionHasErrors(['name', 'email']);
        $this->post('/subscribe', ['email' => str_repeat('a', 200).'@example.com'])->assertSessionHasErrors('email');
        $this->post('/track', ['number' => str_repeat('9', 31), 'email' => 'a@example.com'])->assertSessionHasErrors('number');
    }

    public function test_uploads_accept_only_images_and_pdf_within_limits(): void
    {
        $ok = ['name' => 'Sara', 'phone' => '03001234567'];
        $this->post('/upload-prescription', $ok + ['files' => [UploadedFile::fake()->create('x.php', 10, 'application/x-php')]])->assertSessionHasErrors('files.0');
        $this->post('/upload-prescription', $ok + ['files' => [UploadedFile::fake()->create('x.svg', 10, 'image/svg+xml')]])->assertSessionHasErrors('files.0');
        $this->post('/upload-prescription', $ok + ['files' => [UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf')]])->assertSessionHasErrors('files.0');
        $this->post('/upload-prescription', $ok + ['files' => array_fill(0, 6, UploadedFile::fake()->image('a.jpg'))])->assertSessionHasErrors('files');
        $this->post('/upload-prescription', $ok)->assertSessionHasErrors('files');
        $this->post('/upload-prescription', ['name' => '<b>x</b>', 'phone' => '1', 'files' => [UploadedFile::fake()->image('a.jpg')]])->assertSessionHasErrors(['name', 'phone']);
    }

    public function test_bots_are_stopped_by_the_honeypot_and_by_rate_limits(): void
    {
        $form = ['name' => 'Sara', 'email' => 'a@example.com', 'message' => 'Hello there, need help'];
        $h = ['X-Enforce-Honeypot' => '1'];
        // no signed timestamp = never rendered = a script
        $this->withHeaders($h)->post('/contact', $form)->assertSessionHas('error');
        // a filled trap field
        $ts = \Illuminate\Support\Facades\Crypt::encryptString((string) (time() - 30));
        $this->withHeaders($h)->post('/contact', $form + ['nx_ts' => $ts, 'nx_website' => 'http://spam.example'])->assertSessionHas('error');
        $this->assertSame(0, ContactMessage::count());

        for ($i = 0; $i < 5; $i++) {
            $this->post('/contact', $form);
        }
        $this->post('/contact', $form)->assertStatus(429);
    }

    public function test_login_locks_after_repeated_failures_and_never_reveals_which_part_was_wrong(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        User::factory()->create(['email' => 'real@example.com', 'password' => 'secret-pass-1']);
        $a = $this->post('/login', ['email' => 'real@example.com', 'password' => 'wrong-pass-9']);
        $b = $this->post('/login', ['email' => 'nobody@example.com', 'password' => 'wrong-pass-9']);
        $a->assertSessionHasErrors('email');
        $b->assertSessionHasErrors('email');
        $this->assertSame($a->getSession()->get('errors')->first('email'), $b->getSession()->get('errors')->first('email'));
        for ($i = 0; $i < 8; $i++) {
            $this->post('/login', ['email' => 'real@example.com', 'password' => 'wrong-pass-9']);
        }
        $this->post('/login', ['email' => 'real@example.com', 'password' => 'secret-pass-1'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_stored_text_is_escaped_on_output(): void
    {
        $user = User::factory()->create(['name' => '<script>alert(1)</script>']);   // even if bad data got in another way
        $this->actingAs($user)->get('/account')->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    }
}
