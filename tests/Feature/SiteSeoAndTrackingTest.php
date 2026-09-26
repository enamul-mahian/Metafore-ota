<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteSeoAndTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_lists_public_pages_only(): void
    {
        $response = $this->get(route('sitemap'));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<loc>'.route('home').'</loc>', false)
            ->assertDontSee('/admin', false)
            ->assertDontSee('/account', false);
    }

    public function test_robots_txt_points_to_sitemap_and_blocks_private_areas(): void
    {
        $this->get(route('robots'))
            ->assertOk()
            ->assertSee('Disallow: /admin')
            ->assertSee('Sitemap: '.route('sitemap'));
    }

    public function test_public_pages_have_seo_meta_and_no_tracking_until_connected(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="index,follow">', false)
            ->assertSee('<link rel="canonical" href="'.route('home').'">', false)
            ->assertSee('property="og:title"', false)
            ->assertDontSee('googletagmanager.com', false)
            ->assertDontSee('connect.facebook.net', false);
    }

    public function test_private_pages_are_not_indexed(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $this->actingAs($customer)
            ->get(route('account.overview'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex,nofollow">', false);
    }
}
