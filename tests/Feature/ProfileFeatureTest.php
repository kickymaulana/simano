<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationRule;
use App\Models\EvaluationTemplate;
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

    public function test_profile_lists_pending_evaluators_for_selected_period(): void
    {
        $period = EvaluationPeriod::create(['month' => 10, 'year' => 2026, 'status' => 'active']);
        $template = EvaluationTemplate::create(['target_category' => 'atasan', 'active' => true]);
        $department = Department::factory()->create();
        $factory = Factory::factory()->create();
        $targetPosition = Position::factory()->create(['name' => 'MANAGER']);
        $evaluatorPosition = Position::factory()->create(['name' => 'LEADER']);
        $target = User::factory()->create(['position_id' => $targetPosition->id, 'evaluation_template_id' => $template->id, 'active' => true, 'is_approved' => true]);
        $pending = User::factory()->create(['nik' => 'PENDING-001', 'name' => 'Pending Evaluator', 'position_id' => $evaluatorPosition->id, 'active' => true, 'is_approved' => true]);
        $done = User::factory()->create(['nik' => 'DONE-001', 'name' => 'Done Evaluator', 'position_id' => $evaluatorPosition->id, 'active' => true, 'is_approved' => true]);
        $target->departments()->sync([$department->id]);
        $pending->departments()->sync([$department->id]);
        $done->departments()->sync([$department->id]);
        $target->factories()->sync([$factory->id]);
        $pending->factories()->sync([$factory->id]);
        $done->factories()->sync([$factory->id]);
        EvaluationRule::create(['evaluator_position_id' => $evaluatorPosition->id, 'target_position_id' => $targetPosition->id, 'same_department' => true, 'same_factory' => true, 'active' => true]);
        Evaluation::create(['evaluator_id' => $done->id, 'target_id' => $target->id, 'evaluation_template_id' => $template->id, 'evaluation_period_id' => $period->id, 'target_category' => 'atasan', 'average_score' => 4]);
        Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
        $target->assignRole('employee');

        $this->actingAs($target)->get(route('profile.pending-evaluators', ['period' => $period->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Profile/PendingEvaluators')->where('pendingEvaluators.data.0.nik', 'PENDING-001')->where('pendingEvaluators.total', 1));
    }
}
