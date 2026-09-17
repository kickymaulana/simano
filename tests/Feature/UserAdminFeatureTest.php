<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\EvaluationTemplate;
use App\Models\Factory;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserAdminFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_users_route(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('sso.login'));
    }

    public function test_employee_cannot_access_users_route(): void
    {
        $employee = $this->userWithRole('employee');

        $this->actingAs($employee)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_admin_sees_only_approved_users(): void
    {
        $admin = $this->userWithRole('admin');
        $approved = User::factory()->create(['is_approved' => true]);
        $pending = User::factory()->create(['is_approved' => false, 'requested_role' => 'employee']);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk()->assertSee($approved->name)->assertDontSee($pending->name);
    }

    public function test_admin_updates_user_profile_and_relations(): void
    {
        $admin = $this->userWithRole('admin');
        Role::firstOrCreate(['name' => 'hr', 'guard_name' => 'web']);
        $user = User::factory()->create(['is_approved' => true]);
        $position = Position::factory()->create();
        $department = Department::factory()->create();
        $departmentTwo = Department::factory()->create();
        $factory = Factory::factory()->create();

        $response = $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'name' => 'Nama Nieuwe',
            'role' => 'hr',
            'position_id' => $position->id,
            'department_ids' => [$department->id, $departmentTwo->id],
            'factory_ids' => [$factory->id],
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $user->refresh();

        $this->assertEquals('Nama Nieuwe', $user->name);
        $this->assertEquals('hr', $user->role);
        $this->assertTrue($user->hasRole('hr'));
        $this->assertEquals($position->id, $user->position_id);
        $this->assertEquals($department->id, $user->department_id);
        $this->assertTrue($user->departments()->whereKey($department->id)->exists());
        $this->assertTrue($user->departments()->whereKey($departmentTwo->id)->exists());
        $this->assertTrue($user->factories()->whereKey($factory->id)->exists());
    }

    public function test_admin_applies_active_template_to_filtered_users(): void
    {
        $admin = $this->userWithRole('admin');
        $position = Position::factory()->create();
        $template = EvaluationTemplate::create(['target_category' => 'Atasan', 'active' => true]);
        $matched = User::factory()->create(['is_approved' => true, 'position_id' => $position->id]);
        $unmatched = User::factory()->create(['is_approved' => true]);

        $response = $this->actingAs($admin)->post(route('admin.users.bulk-template'), [
            'template_id' => $template->id,
            'position_id' => $position->id,
        ]);

        $response->assertRedirect();
        $this->assertEquals($template->id, $matched->refresh()->evaluation_template_id);
        $this->assertNull($unmatched->refresh()->evaluation_template_id);
    }

    public function test_admin_toggles_user_active_state(): void
    {
        $admin = $this->userWithRole('admin');
        $user = User::factory()->create(['is_approved' => true, 'active' => true]);

        $this->actingAs($admin)->post(route('admin.users.toggle', $user))->assertRedirect();
        $user->refresh();
        $this->assertFalse($user->active);

        $this->actingAs($admin)->post(route('admin.users.toggle', $user))->assertRedirect();
        $user->refresh();
        $this->assertTrue($user->active);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        $user->assignRole($role);

        return $user;
    }
}
