<?php

namespace Tests\Feature\Admin;

use App\Models\Enquiry;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_dashboard_shows_real_counts_and_latest_enquiries(): void
    {
        Enquiry::factory()->count(2)->create(['name' => 'Karim Test']);
        Enquiry::factory()->create(['status' => Enquiry::STATUS_CLOSED]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['New enquiries', '2', '3 in the last 7 days'])
            ->assertSee('Karim Test')
            ->assertSee('Open work visa files')
            ->assertSee(route('admin.website.images'), false);
    }

    public function test_dashboard_hides_numbers_the_admin_may_not_see(): void
    {
        Enquiry::factory()->create(['name' => 'Hidden Lead']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $admin->roles()->first()->revokePermissionTo('enquiries.view');

        $this->actingAs($admin->fresh())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('New enquiries')
            ->assertDontSee('Hidden Lead');
    }
}
