<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Product;
use App\Support\PageText;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
    }

    public function test_page_text_formats_safely(): void
    {
        $html = PageText::render("# Our promise\n\nWe ship <script>alert(1)</script> fast.\nSecond line.\n\n- one\n- two\n\n1. first\n2. second\n\nSee https://example.com/shop, thanks.");
        $this->assertStringContainsString('<h2>Our promise</h2>', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('fast.<br>Second line.', $html);
        $this->assertStringContainsString('<ul><li>one</li><li>two</li></ul>', $html);
        $this->assertStringContainsString('<ol><li>first</li><li>second</li></ol>', $html);
        $this->assertStringContainsString('<a href="https://example.com/shop" target="_blank" rel="noopener noreferrer nofollow">https://example.com/shop</a>,', $html);
        $this->assertSame('', PageText::render('   '));
        $this->assertStringNotContainsString('href="javascript', PageText::render('javascript:alert(1) [x](javascript:alert(1))'));
    }

    public function test_cms_page_shows_body_other_pages_and_contact_box(): void
    {
        setting()->setMany(['store.email' => 'hi@shop.test', 'store.whatsapp' => '+92 300 1234567']);
        Page::create(['title' => 'About us', 'slug' => 'about', 'body' => "# Who we are\n\nA small shop.", 'is_published' => true]);
        Page::create(['title' => 'Returns', 'slug' => 'returns', 'body' => 'x', 'is_published' => true]);
        Page::create(['title' => 'Secret draft', 'slug' => 'draft', 'body' => 'x', 'is_published' => false]);

        $this->get('/page/about')->assertOk()->assertSee('mk-cms', false)->assertSee('<h2>Who we are</h2>', false)->assertSee('Returns')->assertDontSee('Secret draft')
            ->assertSee('hi@shop.test')->assertSee('wa.me/923001234567', false);
        $this->get('/page/draft')->assertNotFound();
    }

    public function test_wishlist_page_empty_state_suggests_products_and_filled_state_lists_them(): void
    {
        $p = new Product;
        $p->forceFill(['name' => 'Lucky Mug', 'price' => 50, 'status' => 'active', 'track_stock' => false, 'is_featured' => true])->save();

        $this->get('/wishlist')->assertOk()->assertSee('Nothing saved yet')->assertSee('You may like')->assertSee('Lucky Mug');
        $this->postJson("/wishlist/{$p->id}")->assertOk()->assertJson(['added' => true]);
        $this->get('/wishlist')->assertOk()->assertDontSee('Nothing saved yet')->assertSee('Lucky Mug')->assertSee('1 item');
    }
}
