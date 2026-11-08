<?php

namespace Tests\Feature;

use App\Support\Locales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_setup_page_switches_between_english_urdu_and_roman_urdu(): void
    {
        $this->get('/setup')->assertSee('Let’s set up your store', false)->assertSee('dir="ltr"', false);

        $this->get('/lang/ur')->assertRedirect();
        $this->get('/setup')->assertSee('آئیے آپ کا اسٹور سیٹ اپ کریں')->assertSee('dir="rtl"', false);

        $this->get('/lang/ur_Latn');
        $this->get('/setup')->assertSee('Aayiye aap ka store set up karein')->assertSee('dir="ltr"', false);
    }

    public function test_unknown_locale_is_rejected(): void
    {
        $this->get('/lang/xx')->assertNotFound();
    }

    public function test_chosen_language_becomes_store_default_after_setup(): void
    {
        $this->get('/lang/ur');
        $this->post('/setup', [
            'store_name' => 'دکان', 'currency' => 'PKR', 'currency_symbol' => 'Rs',
            'timezone' => 'Asia/Karachi', 'name' => 'Owner', 'email' => 'o@example.com',
            'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1',
        ])->assertRedirect('/admin');

        $this->assertSame('ur', Locales::default());
        $this->assertSame('rtl', Locales::dir('ur'));
    }

    public function test_every_translation_file_covers_the_same_keys(): void
    {
        $ur = array_keys(json_decode(file_get_contents(lang_path('ur.json')), true));
        $roman = array_keys(json_decode(file_get_contents(lang_path('ur_Latn.json')), true));

        $this->assertEqualsCanonicalizing($ur, $roman);
    }
}
