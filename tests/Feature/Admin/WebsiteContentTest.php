<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\SiteContentService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebsiteContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_is_redirected_and_customer_is_forbidden(): void
    {
        $this->get(route('admin.website.images'))->assertRedirect(route('login'));

        $customer = $this->user('customer');
        $this->actingAs($customer)->get(route('admin.website.images'))->assertForbidden();
        $this->actingAs($customer)->put(route('admin.website.contact.update'), ['phone' => '+8801700000000'])->assertForbidden();

        $this->assertSame([], app(SiteContentService::class)->contact());
    }

    public function test_admin_can_open_every_website_tab(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)->get(route('admin.website.images'))->assertOk()->assertSee('Homepage hero');
        $this->actingAs($admin)->get(route('admin.website.destinations'))->assertOk()->assertSee('Dubai');
        $this->actingAs($admin)->get(route('admin.website.seo'))->assertOk()->assertSee('Meta title');
        $this->actingAs($admin)->get(route('admin.website.tracking'))->assertOk()->assertSee('Meta (Facebook) Pixel ID');
        $this->actingAs($admin)->get(route('admin.website.contact'))->assertOk()->assertSee('Save contact details');
    }

    public function test_admin_can_replace_and_restore_an_image(): void
    {
        Storage::fake(SiteContentService::IMAGE_DISK);
        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->post(route('admin.website.images.update', 'home.hero'), [
                'image' => UploadedFile::fake()->image('hero.jpg', 1600, 600),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $stored = app(SiteContentService::class)->section('images')['home.hero'];
        Storage::disk(SiteContentService::IMAGE_DISK)->assertExists($stored);

        $this->get(route('home'))->assertSee(Storage::disk(SiteContentService::IMAGE_DISK)->url($stored), false);

        $this->actingAs($admin)->delete(route('admin.website.images.reset', 'home.hero'))->assertRedirect();

        Storage::disk(SiteContentService::IMAGE_DISK)->assertMissing($stored);
        $this->assertFalse($this->freshSite()->hasCustomImage('home.hero'));
    }

    public function test_image_upload_rejects_unknown_keys_and_non_images(): void
    {
        Storage::fake(SiteContentService::IMAGE_DISK);
        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->post(route('admin.website.images.update', 'not.a.key'), ['image' => UploadedFile::fake()->image('x.jpg')])
            ->assertNotFound();

        $this->actingAs($admin)
            ->post(route('admin.website.images.update', 'home.hero'), ['image' => UploadedFile::fake()->create('evil.php', 10, 'text/x-php')])
            ->assertSessionHasErrors('image');

        $this->assertFalse($this->freshSite()->hasCustomImage('home.hero'));
    }

    public function test_admin_can_edit_homepage_destinations(): void
    {
        Storage::fake(SiteContentService::IMAGE_DISK);

        $this->actingAs($this->user('admin'))
            ->put(route('admin.website.destinations.update'), [
                'items' => [
                    ['name' => ' Kathmandu ', 'country' => 'Nepal', 'image' => '', 'sort' => 2, 'active' => '1',
                        'upload' => UploadedFile::fake()->image('ktm.jpg')],
                    ['name' => 'Dubai', 'country' => 'UAE', 'image' => 'dest.dubai', 'sort' => 1, 'active' => '0'],
                    ['name' => 'Bali', 'country' => 'Indonesia', 'image' => 'dest.bali', 'sort' => 3, 'active' => '1', 'remove' => '1'],
                    ['name' => 'Hacked', 'image' => '../../.env', 'sort' => 4, 'active' => '1'],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $site = $this->freshSite();
        $all = $site->destinations();

        $this->assertSame(['Dubai', 'Kathmandu', 'Hacked'], array_column($all, 'name'));
        $this->assertStringStartsWith(SiteContentService::IMAGE_DIRECTORY.'/', $all[1]['image']);
        $this->assertSame('', $all[2]['image']);
        $this->assertSame(['Kathmandu', 'Hacked'], array_column($site->destinations(true), 'name'));
    }

    public function test_admin_can_override_seo_and_public_page_uses_it(): void
    {
        $this->actingAs($this->user('admin'))
            ->put(route('admin.website.seo.update'), [
                'pages' => ['home' => ['title' => 'Cheap flights from Dhaka | Eagle Global Hub', 'description' => 'Book flights, hotels and visas from Bangladesh.']],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<title>Cheap flights from Dhaka | Eagle Global Hub</title>', false)
            ->assertSee('<meta name="description" content="Book flights, hotels and visas from Bangladesh.">', false);
    }

    public function test_tracking_ids_are_validated_and_rendered_on_public_pages(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->put(route('admin.website.tracking.update'), ['google_analytics_id' => '"><script>alert(1)</script>', 'meta_pixel_id' => 'abc'])
            ->assertSessionHasErrors(['google_analytics_id', 'meta_pixel_id']);

        $this->actingAs($admin)
            ->put(route('admin.website.tracking.update'), ['google_analytics_id' => 'G-ABC123XYZ', 'meta_pixel_id' => '123456789012345'])
            ->assertSessionHasNoErrors();

        $this->get(route('home'))
            ->assertSee('googletagmanager.com/gtag/js?id=G-ABC123XYZ', false)
            ->assertSee("fbq('init', \"123456789012345\")", false);
    }

    public function test_only_super_admin_can_save_custom_code(): void
    {
        $snippet = '<script>window.partnerTag = true;</script>';

        $this->actingAs($this->user('admin'))
            ->put(route('admin.website.custom-code.update'), ['head' => $snippet])
            ->assertForbidden();
        $this->get(route('home'))->assertDontSee($snippet, false);

        $this->actingAs($this->user('super-admin'))
            ->put(route('admin.website.custom-code.update'), ['head' => $snippet])
            ->assertRedirect();
        $this->get(route('home'))->assertSee($snippet, false);
    }

    public function test_contact_details_are_validated_and_shown_only_when_filled(): void
    {
        $admin = $this->user('admin');

        $this->get(route('home'))->assertDontSee('tel:', false);

        $this->actingAs($admin)
            ->put(route('admin.website.contact.update'), ['email' => 'not-an-email', 'facebook_url' => 'javascript:alert(1)'])
            ->assertSessionHasErrors(['email', 'facebook_url']);

        $this->actingAs($admin)
            ->put(route('admin.website.contact.update'), ['phone' => '+880 1700 000000', 'email' => 'hello@eagleglobalhub.test'])
            ->assertSessionHasNoErrors();

        $this->get(route('home'))->assertSee('hello@eagleglobalhub.test')->assertSee('tel:', false);
    }

    public function test_site_settings_are_hidden_from_generic_settings_page(): void
    {
        $this->freshSite()->saveSection('tracking', ['meta_pixel_id' => '123456789012345']);

        $this->actingAs($this->user('super-admin'))
            ->get(route('admin.settings.manage'))
            ->assertOk()
            ->assertDontSee('123456789012345');
    }

    private function freshSite(): SiteContentService
    {
        app()->forgetScopedInstances();

        return app(SiteContentService::class);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function user(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole($role);

        return $user;
    }
}
