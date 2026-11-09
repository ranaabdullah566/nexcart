<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\HomeSection;
use App\Models\User;
use App\Services\DemoData;
use App\Support\BusinessProfile;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
        BusinessProfile::apply('pharmacy');
        Storage::fake('public');
        config(['media-library.disk_name' => 'public']);
    }

    private function article(array $o = []): Article
    {
        $a = new Article;
        $a->forceFill($o + ['title' => 'Sleep well', 'tag' => 'Wellness', 'body' => 'Go to bed early.', 'is_published' => true, 'published_at' => now()->subDay()])->save();

        return $a;
    }

    private function user(string $role): User
    {
        return tap(User::factory()->create(), fn ($u) => $u->assignRole($role));
    }

    public function test_blog_lists_only_live_articles_and_filters_by_topic_and_search(): void
    {
        $this->article(['title' => 'Hydration basics', 'tag' => 'Wellness', 'body' => 'Drink water often.']);
        $this->article(['title' => 'Sun care', 'tag' => 'Skin care', 'body' => 'Wear sunscreen.']);
        $this->article(['title' => 'Secret draft', 'is_published' => false]);
        $this->article(['title' => 'Coming soon', 'published_at' => now()->addWeek()]);

        $this->get('/blog')->assertOk()->assertSee('Hydration basics')->assertSee('Sun care')->assertDontSee('Secret draft')->assertDontSee('Coming soon')
            ->assertSee('Wellness')->assertSee('Skin care')->assertSee('not medical advice');
        $this->get('/blog?tag=Skin+care')->assertSee('Sun care')->assertDontSee('Hydration basics');
        $this->get('/blog?q=water')->assertSee('Hydration basics')->assertDontSee('Sun care');
        $this->get('/blog?q=zzzz')->assertSee('No articles found');
    }

    public function test_article_page_renders_with_seo_reading_time_and_related(): void
    {
        $a = $this->article(['title' => 'Antibiotics 101', 'body' => implode(' ', array_fill(0, 450, 'word')), 'author' => 'Dr. Sana', 'meta_title' => 'Antibiotics guide', 'meta_description' => 'Use them wisely']);
        $this->article(['title' => 'Other read', 'tag' => 'Wellness']);

        $html = $this->get('/blog/'.$a->slug)->assertOk()->assertSee('Antibiotics 101')->assertSee('Dr. Sana')->assertSee('3 min read')->assertSee('Other read')
            ->assertSee('Shop medicines')->assertSee('Upload prescription')->assertSee('not medical advice')->getContent();
        $this->assertStringContainsString('<title>Antibiotics guide', $html);
        $this->assertStringContainsString('name="description" content="Use them wisely"', $html);
        $this->assertStringContainsString('og:type" content="article"', $html);
    }

    public function test_drafts_scheduled_and_missing_articles_are_not_found(): void
    {
        $d = $this->article(['title' => 'Draft one', 'is_published' => false]);
        $f = $this->article(['title' => 'Future one', 'published_at' => now()->addDay()]);
        $this->get('/blog/'.$d->slug)->assertNotFound();
        $this->get('/blog/'.$f->slug)->assertNotFound();
        $this->get('/blog/nope')->assertNotFound();
    }

    public function test_body_is_escaped_but_simple_formatting_works(): void
    {
        $a = $this->article(['body' => "# Tips\n\n- one\n- two\n\n<script>alert(1)</script>"]);
        $html = $this->get('/blog/'.$a->slug)->assertOk()->getContent();
        $this->assertStringContainsString('<h2>Tips</h2>', $html);
        $this->assertStringContainsString('<li>one</li>', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
    }

    public function test_admin_can_write_schedule_translate_and_delete_articles(): void
    {
        $this->actingAs($this->user('manager'));
        $this->get('/admin/articles')->assertOk();
        $this->get('/admin/articles/create')->assertOk();

        $this->post('/admin/articles', ['title' => ''])->assertSessionHasErrors(['title', 'body']);
        $this->post('/admin/articles', ['title' => 'Cough care', 'body' => 'Rest and fluids.', 'cover' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])->assertSessionHasErrors('cover');

        $this->post('/admin/articles', ['title' => 'Cough care', 'tag' => 'Cold & flu', 'author' => 'Dr. Ali', 'body' => 'Rest and fluids.', 'is_published' => 1,
            'cover' => UploadedFile::fake()->image('c.jpg', 1200, 700), 'tr' => ['ur_Latn' => ['title' => 'Khansi ki dekh bhaal']]])->assertRedirect('/admin/articles');
        $a = Article::firstWhere('title', 'Cough care');
        $this->assertSame(['cough-care', true, 1], [$a->slug, $a->is_published, $a->getMedia('cover')->count()]);
        $this->assertNotNull($a->published_at);
        $this->assertSame('Khansi ki dekh bhaal', $a->trans('title', 'ur_Latn'));
        $this->get('/blog')->assertSee('Cough care');

        $this->put("/admin/articles/{$a->id}", ['title' => 'Cough care', 'body' => 'Updated.', 'published_at' => now()->addDays(3)->format('Y-m-d\TH:i'), 'is_published' => 1, 'remove_cover' => 1])->assertRedirect();
        $this->assertSame(0, $a->fresh()->getMedia('cover')->count());
        $this->get('/blog')->assertDontSee('Cough care');   // scheduled for the future
        $this->get('/admin/articles')->assertSee('Scheduled');

        $this->delete("/admin/articles/{$a->id}")->assertRedirect('/admin/articles');
        $this->assertSoftDeleted($a);
    }

    public function test_only_storefront_managers_can_use_the_article_admin(): void
    {
        $a = $this->article();
        $this->actingAs($this->user('rider'));
        $this->get('/admin/articles')->assertForbidden();
        $this->post('/admin/articles', ['title' => 'x', 'body' => 'y'])->assertForbidden();
        $this->delete("/admin/articles/{$a->id}")->assertForbidden();
    }

    public function test_home_section_and_menu_link_appear_only_when_there_are_articles(): void
    {
        $this->get('/')->assertDontSee('From our health blog')->assertDontSee(route('store.blog'), false);

        $this->article(['title' => 'Fresh tips']);
        $this->get('/')->assertSee('From our health blog')->assertSee('Fresh tips')->assertSee(route('store.blog'), false);

        $this->actingAs($this->user('owner'));
        $s = HomeSection::where('type', 'blog')->firstOrFail();
        $this->put("/admin/home-sections/{$s->id}", ['title' => 'Read more', 'limit' => 2, 'is_active' => 1])->assertRedirect();
        $this->get('/')->assertSee('Read more');
        $this->put("/admin/home-sections/{$s->id}", ['title' => 'x', 'limit' => 50])->assertSessionHasErrors('limit');

        setting()->set('sf.header.show_blog', false);
        $this->get('/')->assertDontSee('mk-cats-row > a[href="'.route('store.blog').'"]', false);
    }

    public function test_sitemap_lists_live_articles_and_demo_data_cleans_up(): void
    {
        $a = $this->article(['title' => 'Mapped']);
        $draft = $this->article(['title' => 'Hidden', 'is_published' => false]);
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        $this->assertStringContainsString(route('store.article', $a->slug), $xml);
        $this->assertStringNotContainsString(route('store.article', $draft->slug), $xml);

        $demo = app(DemoData::class);
        $before = Article::count();
        $demo->load();
        $this->assertSame($before + 6, Article::count());
        $demo->remove();
        $this->assertSame($before, Article::count());
        $this->assertNotNull(Article::find($a->id));
    }
}
