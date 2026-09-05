<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\Admin\RedactedSystemLogReader;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SystemLogPageTest extends TestCase
{
    use RefreshDatabase;

    private string $logDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->logDirectory = storage_path(
            'framework/testing/system-logs-'.uniqid('', true),
        );
        mkdir($this->logDirectory, 0777, true);
        $this->app->instance(
            RedactedSystemLogReader::class,
            new RedactedSystemLogReader($this->logDirectory),
        );
    }

    protected function tearDown(): void
    {
        foreach (glob($this->logDirectory.DIRECTORY_SEPARATOR.'*') ?: [] as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        if (is_dir($this->logDirectory)) {
            rmdir($this->logDirectory);
        }

        parent::tearDown();
    }

    public function test_guest_is_redirected_from_system_logs(): void
    {
        $this->get(route('admin.system-logs.index'))
            ->assertRedirect(route('login'));
    }

    public function test_customer_and_admin_are_forbidden_from_system_logs(): void
    {
        foreach (['customer', 'admin'] as $role) {
            $this->actingAs($this->user($role))
                ->get(route('admin.system-logs.index'))
                ->assertForbidden();
        }
    }

    public function test_super_admin_can_view_metadata_only_system_logs(): void
    {
        $this->writeLog(implode(PHP_EOL, [
            '[2026-09-05 10:00:00] production.ERROR: Payment failed for traveler Jane Doe jane@example.com {"password":"plain-secret","access_token":"token-secret","card":"4111111111111111"}',
            '#0 C:\\private\\application\\stack.php(99): hidden()',
            '[2026-09-05 10:01:00] production.INFO: Raw request payload {"passport":"AB1234567","api_key":"api-secret"}',
        ]));

        $this->actingAs($this->user('super-admin'))
            ->get(route('admin.system-logs.index'))
            ->assertOk()
            ->assertSee('Metadata-only security view.')
            ->assertSee('2026-09-05 10:01:00')
            ->assertSee('Informational application event recorded')
            ->assertSee('2026-09-05 10:00:00')
            ->assertSee('Application error recorded')
            ->assertSeeInOrder([
                '2026-09-05 10:01:00',
                '2026-09-05 10:00:00',
            ])
            ->assertDontSee('Payment failed')
            ->assertDontSee('Jane Doe')
            ->assertDontSee('jane@example.com')
            ->assertDontSee('plain-secret')
            ->assertDontSee('token-secret')
            ->assertDontSee('4111111111111111')
            ->assertDontSee('Raw request payload')
            ->assertDontSee('AB1234567')
            ->assertDontSee('api-secret')
            ->assertDontSee('stack.php')
            ->assertDontSee($this->logDirectory);
    }

    public function test_super_admin_without_permission_is_forbidden(): void
    {
        $superAdmin = $this->user('super-admin');
        Role::findByName('super-admin')->revokePermissionTo('system-logs.view');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($superAdmin->fresh())
            ->get(route('admin.system-logs.index'))
            ->assertForbidden();
    }

    public function test_severity_filter_is_validated_and_preserved(): void
    {
        $this->writeLog(implode(PHP_EOL, [
            '[2026-09-05 10:00:00] production.ERROR: Secret error detail',
            '[2026-09-05 10:01:00] production.WARNING: Secret warning detail',
        ]));
        $superAdmin = $this->user('super-admin');

        $this->actingAs($superAdmin)
            ->get(route('admin.system-logs.index', ['level' => 'warning']))
            ->assertOk()
            ->assertSee('value="warning" selected', false)
            ->assertSee('Application warning recorded')
            ->assertDontSee('Application error recorded')
            ->assertDontSee('Secret warning detail');

        $this->actingAs($superAdmin)
            ->from(route('admin.system-logs.index'))
            ->get(route('admin.system-logs.index', ['level' => 'everything']))
            ->assertRedirect(route('admin.system-logs.index'))
            ->assertSessionHasErrors('level');
    }

    public function test_reader_ignores_non_laravel_files_and_bounds_results(): void
    {
        $lines = [];

        foreach (range(1, 101) as $second) {
            $lines[] = sprintf(
                '[2026-09-05 10:%02d:%02d] production.DEBUG: secret-%d',
                intdiv($second, 60),
                $second % 60,
                $second,
            );
        }

        $this->writeLog(implode(PHP_EOL, $lines));
        file_put_contents(
            $this->logDirectory.DIRECTORY_SEPARATOR.'private.log',
            '[2026-09-06 00:00:00] production.CRITICAL: should-not-be-read',
        );

        $this->actingAs($this->user('super-admin'))
            ->get(route('admin.system-logs.index'))
            ->assertOk()
            ->assertViewHas(
                'logData',
                fn (array $data): bool => $data['filesInspected'] === 1
                    && count($data['entries']) === 100
                    && $data['truncated'] === true
                    && $data['levelCounts']['debug'] === 101
                    && $data['levelCounts']['critical'] === 0,
            )
            ->assertDontSee('should-not-be-read')
            ->assertDontSee('secret-101');
    }

    public function test_navigation_link_is_real_and_super_admin_only_on_both_surfaces(): void
    {
        $superAdmin = $this->user('super-admin');

        $this->actingAs($superAdmin)
            ->get(route('admin.system-logs.index'))
            ->assertOk()
            ->assertSee(route('admin.system-logs.index'), false);

        $this->actingAs($superAdmin)
            ->get(route('admin.settings.manage'))
            ->assertOk()
            ->assertSee(route('admin.system-logs.index'), false);

        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertDontSee(route('admin.system-logs.index'), false);

        $this->actingAs($admin)
            ->get(route('admin.settings.manage'))
            ->assertOk()
            ->assertDontSee(route('admin.system-logs.index'), false);
    }

    public function test_system_logs_expose_no_mutation_routes(): void
    {
        $this->assertFalse(Route::has('admin.system-logs.store'));
        $this->assertFalse(Route::has('admin.system-logs.update'));
        $this->assertFalse(Route::has('admin.system-logs.destroy'));

        $this->actingAs($this->user('super-admin'))
            ->post(route('admin.system-logs.index'))
            ->assertMethodNotAllowed();
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function writeLog(string $contents): void
    {
        file_put_contents(
            $this->logDirectory.DIRECTORY_SEPARATOR.'laravel.log',
            $contents.PHP_EOL,
        );
    }
}
