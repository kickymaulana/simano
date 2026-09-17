<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationTemplate;
use App\Models\Factory;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_authenticated_routes(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('sso.login'));
    }

    public function test_employee_cannot_access_admin_routes(): void
    {
        $employee = $this->userWithRole('employee');

        $this->actingAs($employee)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_admin_can_create_period_and_only_one_period_is_active(): void
    {
        $admin = $this->userWithRole('admin');
        EvaluationPeriod::create(['month' => 8, 'year' => 2026, 'status' => 'active']);

        $this->actingAs($admin)->post(route('admin.evaluation-periods.store'), [
            'month' => 9,
            'year' => 2026,
            'status' => 'active',
        ])->assertRedirect(route('admin.evaluation-periods.index'));

        $this->assertDatabaseHas('evaluation_periods', ['month' => 8, 'year' => 2026, 'status' => 'closed']);
        $this->assertDatabaseHas('evaluation_periods', ['month' => 9, 'year' => 2026, 'status' => 'active']);
    }

    public function test_admin_lists_are_paginated(): void
    {
        $admin = $this->userWithRole('admin');
        User::factory()->count(11)->create(['is_approved' => true]);
        Department::factory()->count(11)->create();

        foreach (range(2026, 2036) as $year) {
            EvaluationPeriod::create(['month' => 1, 'year' => $year, 'status' => 'closed']);
        }

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('users.total', 11)->where('users.last_page', 2)->has('users.data', 10));
        $this->actingAs($admin)->get(route('admin.departments.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('departments.total', 11)->where('departments.last_page', 2)->has('departments.data', 10));
        $this->actingAs($admin)->get(route('admin.evaluation-periods.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('periods.total', 11)->where('periods.last_page', 2)->has('periods.data', 10));
    }

    public function test_period_with_evaluations_cannot_be_deleted(): void
    {
        $admin = $this->userWithRole('admin');
        $evaluator = User::factory()->create();
        $target = User::factory()->create();
        $period = EvaluationPeriod::create(['month' => 9, 'year' => 2026, 'status' => 'active']);
        $template = EvaluationTemplate::create(['target_category' => 'atasan', 'active' => true]);
        Evaluation::create([
            'evaluator_id' => $evaluator->id,
            'target_id' => $target->id,
            'evaluation_template_id' => $template->id,
            'evaluation_period_id' => $period->id,
            'target_category' => 'atasan',
            'average_score' => 4,
        ]);

        $this->actingAs($admin)->delete(route('admin.evaluation-periods.destroy', $period))
            ->assertRedirect(route('admin.evaluation-periods.index'))
            ->assertSessionHasErrors(['period' => 'Periode sudah memiliki evaluasi dan tidak dapat dihapus.']);

        $this->assertDatabaseHas('evaluation_periods', ['id' => $period->id]);
    }

    public function test_period_without_evaluations_can_be_deleted(): void
    {
        $admin = $this->userWithRole('admin');
        $period = EvaluationPeriod::create(['month' => 9, 'year' => 2026, 'status' => 'closed']);

        $this->actingAs($admin)->delete(route('admin.evaluation-periods.destroy', $period))
            ->assertRedirect(route('admin.evaluation-periods.index'));

        $this->assertDatabaseMissing('evaluation_periods', ['id' => $period->id]);
    }

    public function test_template_rejects_duplicate_question_numbers(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->post(route('admin.evaluation-templates.store'), [
            'target_category' => 'atasan',
            'active' => true,
            'questions' => [
                ['question_number' => 1, 'question_text' => 'Satu', 'active' => true],
                ['question_number' => 1, 'question_text' => 'Duplikat', 'active' => true],
            ],
        ])->assertSessionHasErrors('questions');

        $this->assertDatabaseMissing('evaluation_templates', ['target_category' => 'atasan']);
    }

    public function test_admin_can_filter_evaluation_report_by_target_organization(): void
    {
        $admin = $this->userWithRole('admin');
        $evaluator = User::factory()->create();
        $position = Position::factory()->create();
        $department = Department::factory()->create();
        $factory = Factory::factory()->create();
        $matchingTarget = User::factory()->create(['position_id' => $position->id]);
        $matchingTarget->departments()->sync([$department->id]);
        $matchingTarget->factories()->sync([$factory->id]);
        $otherTarget = User::factory()->create();
        $period = EvaluationPeriod::create(['month' => 9, 'year' => 2026, 'status' => 'active']);
        $template = EvaluationTemplate::create(['target_category' => 'collection', 'active' => true]);

        foreach ([$matchingTarget, $otherTarget] as $target) {
            Evaluation::create([
                'evaluator_id' => $evaluator->id,
                'target_id' => $target->id,
                'evaluation_template_id' => $template->id,
                'evaluation_period_id' => $period->id,
                'target_category' => 'collection',
                'average_score' => 4,
            ]);
        }

        $this->actingAs($admin)->get(route('admin.reports.evaluations', [
            'position_id' => $position->id,
            'department_id' => $department->id,
            'factory_id' => $factory->id,
        ]))->assertOk()->assertInertia(fn ($page) => $page
            ->has('rows.data', 1)
            ->where('rows.data.0.target.id', $matchingTarget->id)
            ->where('filters.position_id', $position->id)
            ->where('filters.department_id', $department->id)
            ->where('filters.factory_id', $factory->id)
            ->has('positions')
            ->has('departments')
            ->has('factories'));
    }

    public function test_admin_report_does_not_expose_evaluator_identity(): void
    {
        $admin = $this->userWithRole('admin');
        $evaluator = User::factory()->create(['nik' => 'NIK-EVALUATOR-SECRET']);
        $target = User::factory()->create(['nik' => 'NIK-TARGET']);
        $period = EvaluationPeriod::create(['month' => 9, 'year' => 2026, 'status' => 'active']);
        $template = EvaluationTemplate::create(['target_category' => 'atasan', 'active' => true]);
        Evaluation::create([
            'evaluator_id' => $evaluator->id,
            'target_id' => $target->id,
            'evaluation_template_id' => $template->id,
            'evaluation_period_id' => $period->id,
            'target_category' => 'atasan',
            'average_score' => 4,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.evaluations'));

        $response->assertOk()->assertDontSee('evaluator_id')->assertDontSee('NIK-EVALUATOR-SECRET');
    }

    public function test_atasan_evaluation_report_can_filter_by_period_and_target(): void
    {
        $admin = $this->userWithRole('admin');
        $period = EvaluationPeriod::create(['month' => 9, 'year' => 2026, 'status' => 'active']);
        $target = User::factory()->create(['is_approved' => true]);

        $this->actingAs($admin)->get(route('admin.reports.evaluations.atasan', ['period' => $period->id, 'target' => $target->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Reports/Atasan')->where('selectedTarget.id', $target->id));

        $this->actingAs($admin)->get(route('admin.reports.evaluations.atasan.evaluators', ['period' => $period->id, 'target' => $target->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Reports/Evaluators')->where('target.id', $target->id)->has('evaluators.data'));
    }

    public function test_atasan_evaluation_report_pdf_uses_target_nik_as_filename(): void
    {
        $admin = $this->userWithRole('admin');
        $period = EvaluationPeriod::create(['month' => 9, 'year' => 2026, 'status' => 'active']);
        $target = User::factory()->create(['nik' => 'NIK-ATASAN-001']);

        $this->actingAs($admin)->get(route('admin.reports.evaluations.atasan.pdf', ['period' => $period->id, 'target' => $target->id]))
            ->assertOk()
            ->assertDownload('Laporan Evaluasi per Atasan - NIK-ATASAN-001.pdf');
    }

    public function test_pending_users_page_includes_nik(): void
    {
        $admin = $this->userWithRole('admin');
        $position = Position::factory()->create(['name' => 'MANAGER']);
        User::factory()->create([
            'nik' => 'NIK-PENDING-001',
            'is_approved' => false,
            'requested_role' => 'employee',
            'requested_position_id' => $position->id,
        ]);

        $this->actingAs($admin)->get(route('admin.pending-users.index'))
            ->assertOk()
            ->assertSee('NIK-PENDING-001')
            ->assertSee('MANAGER');
    }

    public function test_approving_pending_user_syncs_spatie_role_and_clears_request(): void
    {
        $admin = $this->userWithRole('admin');
        Role::firstOrCreate(['name' => 'hr', 'guard_name' => 'web']);
        $position = Position::factory()->create();
        $department = Department::factory()->create();
        $factory = Factory::factory()->create();
        $pending = User::factory()->create([
            'is_approved' => false,
            'requested_role' => 'hr',
            'requested_position_id' => $position->id,
            'requested_department_id' => $department->id,
        ]);
        $pending->requestedDepartments()->sync([$department->id]);
        $pending->requestedFactories()->sync([$factory->id]);

        $this->actingAs($admin)->post(route('admin.pending-users.approve', $pending))
            ->assertRedirect();

        $pending->refresh();

        $this->assertTrue($pending->is_approved);
        $this->assertTrue($pending->active);
        $this->assertNull($pending->requested_role);
        $this->assertNull($pending->requested_position_id);
        $this->assertNull($pending->requested_department_id);
        $this->assertEquals('hr', $pending->role);
        $this->assertEquals($position->id, $pending->position_id);
        $this->assertEquals($department->id, $pending->department_id);
        $this->assertTrue($pending->hasRole('hr'));
        $this->assertTrue($pending->factories()->whereKey($factory->id)->exists());
        $this->assertTrue($pending->departments()->whereKey($department->id)->exists());
        $this->assertDatabaseMissing('requested_factory_user', ['user_id' => $pending->id]);
        $this->assertDatabaseMissing('requested_department_user', ['user_id' => $pending->id]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        $user->assignRole($role);

        return $user;
    }
}
