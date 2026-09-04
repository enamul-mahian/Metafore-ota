<?php

namespace Tests\Feature\Travel;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TravelServiceRouteAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_unverified_customer_is_redirected_from_service_pages(): void
    {
        $user = User::factory()->unverified()->create();
        $user->assignRole('customer');

        foreach (
            ['hotels.index', 'tours.index', 'visa.index'] as $routeName
        ) {
            $this->actingAs($user)
                ->get(route($routeName))
                ->assertRedirect(route('verification.notice'));
        }
    }

    public function test_unverified_customer_is_redirected_before_service_actions(): void
    {
        $user = User::factory()->unverified()->create();
        $user->assignRole('customer');

        foreach (
            ['hotels.search', 'tours.search', 'visa.requirements'] as $routeName
        ) {
            $this->actingAs($user)
                ->post(route($routeName))
                ->assertRedirect(route('verification.notice'));
        }
    }
}
