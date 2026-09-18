<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Factory;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfileFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_profile(): void
    {
        $this->get(route('profile.edit'))->assertRedirect(route('sso.login'));
    }

    public function test_user_can_request_organization_change_without_changing_role_or_current_organization(): void
    {
        $currentPosition = Position::factory()->create();
        $requestedPosition = Position::factory()->create(['name' => 'OPERATOR']);
        $currentDepartment = Department::factory()->create();
        $requestedDepartment = Department::factory()->create();
        $secondRequestedDepartment = Department::factory()->create();
        $currentFactory = Factory::factory()->create();
        $requestedFactory = Factory::factory()->create();
        $secondRequestedFactory = Factory::factory()->create();
        $user = User::factory()->create(['role' => 'employee', 'position_id' => $currentPosition->id]);
        Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
        $user->assignRole('employee');
        $user->departments()->sync([$currentDepartment->id]);
        $user->factories()->sync([$currentFactory->id]);

        $this->actingAs($user)->put(route('profile.update'), [
            'position_id' => $requestedPosition->id,
            'department_ids' => [$requestedDepartment->id, $secondRequestedDepartment->id],
            'factory_ids' => [$requestedFactory->id, $secondRequestedFactory->id],
            'role' => 'admin',
        ])->assertRedirect();

        $user->refresh();
        $this->assertSame('employee', $user->role);
        $this->assertSame($requestedPosition->id, $user->position_id);
        $this->assertSame($requestedDepartment->id, $user->department_id);
        $this->assertTrue($user->departments()->whereKey($requestedDepartment->id)->exists());
        $this->assertTrue($user->departments()->whereKey($secondRequestedDepartment->id)->exists());
        $this->assertTrue($user->factories()->whereKey($requestedFactory->id)->exists());
        $this->assertTrue($user->factories()->whereKey($secondRequestedFactory->id)->exists());
        $this->assertFalse($user->departments()->whereKey($currentDepartment->id)->exists());
        $this->assertFalse($user->factories()->whereKey($currentFactory->id)->exists());
        $this->assertFalse($user->hasRole('admin'));
    }
}
