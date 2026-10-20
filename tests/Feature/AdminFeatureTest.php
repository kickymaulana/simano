<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Evaluation;
use App\Models\EvaluationDetail;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationTemplate;
use App\Models\Factory;
use App\Models\Position;
use App\Models\Question;
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

    public function test_attention_uses_score_four_and_five_percentage_target(): void
    {
        $admin = $this->userWithRole('admin');
        $period = EvaluationPeriod::create(['month' => 9, 'year' => 2026, 'status' => 'active']);
        $template = EvaluationTemplate::create(['target_category' => 'Atasan', 'active' => true]);
        $question = Question::create(['evaluation_template_id' => $template->id, 'question_number' => 1, 'question_text' => 'Pertanyaan', 'active' => true]);
        $targetBelow = User::factory()->create(['active' => true, 'is_approved' => true, 'evaluation_template_id' => $template->id]);
        $targetAtTarget = User::factory()->create(['active' => true, 'is_approved' => true, 'evaluation_template_id' => $template->id]);
        $evaluator = User::factory()->create();
        $belowEvaluation = Evaluation::create(['evaluator_id' => $evaluator->id, 'target_id' => $targetBelow->id, 'evaluation_template_id' => $template->id, 'evaluation_period_id' => $period->id, 'target_category' => 'Atasan', 'average_score' => 4]);
        $atTargetEvaluation = Evaluation::create(['evaluator_id' => $evaluator->id, 'target_id' => $targetAtTarget->id, 'evaluation_template_id' => $template->id, 'evaluation_period_id' => $period->id, 'target_category' => 'Atasan', 'average_score' => 4]);

        foreach ([5, 5, 5, 3, 3] as $index => $score) {
            $detailQuestion = $index === 0 ? $question : Question::create(['evaluation_template_id' => $template->id, 'question_number' => $index + 1, 'question_text' => "Pertanyaan {$index}", 'active' => true]);
            EvaluationDetail::create(['evaluation_id' => $belowEvaluation->id, 'question_id' => $detailQuestion->id, 'score' => $score]);
        }
        foreach ([5, 5, 5, 5, 3] as $index => $score) {
            $detailQuestion = $index === 0 ? $question : Question::where('evaluation_template_id', $template->id)->where('question_number', $index + 1)->firstOrFail();
            EvaluationDetail::create(['evaluation_id' => $atTargetEvaluation->id, 'question_id' => $detailQuestion->id, 'score' => $score]);
        }

        $this->actingAs($admin)->get(route('admin.attention.index'))
            ->assertInertia(fn ($page) => $page->where('filters.threshold', 80)->where('rows.total', 1)->where('rows.data.0.target.id', $targetBelow->id)->where('rows.data.0.score_4_5_percentage', 60));
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
        $matchingTarget->update(['evaluation_template_id' => $template->id]);
        $otherTarget->update(['evaluation_template_id' => $template->id]);

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

    public function test_evaluation_report_aggregates_score_percentages_across_all_target_answers(): void
    {
        $admin = $this->userWithRole('admin');
        EvaluationPeriod::create(['month' => 1, 'year' => 2026, 'status' => 'closed']);
        $period = EvaluationPeriod::create(['month' => 2, 'year' => 2026, 'status' => 'active']);
        $firstEvaluator = User::factory()->create();
        $target = User::factory()->create();
        $secondEvaluator = User::factory()->create();
        $template = EvaluationTemplate::create(['target_category' => 'atasan', 'active' => true]);
        $target->update(['evaluation_template_id' => $template->id]);
        $firstEvaluation = Evaluation::create(['evaluator_id' => $firstEvaluator->id, 'target_id' => $target->id, 'evaluation_template_id' => $template->id, 'evaluation_period_id' => $period->id, 'target_category' => 'atasan', 'average_score' => 4]);
        $secondEvaluation = Evaluation::create(['evaluator_id' => $secondEvaluator->id, 'target_id' => $target->id, 'evaluation_template_id' => $template->id, 'evaluation_period_id' => $period->id, 'target_category' => 'atasan', 'average_score' => 3]);
        $scores = [...array_fill(0, 24, 5), ...array_fill(0, 20, 4), ...array_fill(0, 19, 3), ...array_fill(0, 4, 2), ...array_fill(0, 2, 1)];

        foreach ($scores as $index => $score) {
            $question = Question::create(['evaluation_template_id' => $template->id, 'question_number' => $index + 1, 'question_text' => "Pertanyaan {$index}", 'active' => true]);
            EvaluationDetail::create(['evaluation_id' => $index < 35 ? $firstEvaluation->id : $secondEvaluation->id, 'question_id' => $question->id, 'score' => $score]);
        }

        $this->actingAs($admin)->get(route('admin.reports.evaluations', ['period' => $period->id, 'target' => $target->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('rows.data.0.evaluation_count', 2)
                ->where('rows.data.0.score_percentages.5', 35)
                ->where('rows.data.0.score_percentages.4', 29)
                ->where('rows.data.0.score_percentages.3', 28)
                ->where('rows.data.0.score_percentages.2', 6)
                ->where('rows.data.0.score_percentages.1', 3)
                ->missing('rows.data.0.average_score'));
    }

    public function test_evaluation_report_averages_rounded_question_percentages(): void
    {
        $admin = $this->userWithRole('admin');
        $period = EvaluationPeriod::create(['month' => 2, 'year' => 2026, 'status' => 'active']);
        $template = EvaluationTemplate::create(['target_category' => 'atasan', 'active' => true]);
        $target = User::factory()->create(['evaluation_template_id' => $template->id]);
        $firstQuestion = Question::create(['evaluation_template_id' => $template->id, 'question_number' => 1, 'question_text' => 'Pertanyaan 1', 'active' => true]);
        $secondQuestion = Question::create(['evaluation_template_id' => $template->id, 'question_number' => 2, 'question_text' => 'Pertanyaan 2', 'active' => true]);
        $firstScores = [...array_fill(0, 22, 5), ...array_fill(0, 30, 4), ...array_fill(0, 45, 3), ...array_fill(0, 3, 2)];
        $secondScores = [...array_fill(0, 22, 5), ...array_fill(0, 30, 4), ...array_fill(0, 47, 3), ...array_fill(0, 3, 2)];

        foreach (range(0, 101) as $index) {
            $evaluation = Evaluation::create(['evaluator_id' => User::factory()->create()->id, 'target_id' => $target->id, 'evaluation_template_id' => $template->id, 'evaluation_period_id' => $period->id, 'target_category' => 'atasan', 'average_score' => 4]);
            if (isset($firstScores[$index])) {
                EvaluationDetail::create(['evaluation_id' => $evaluation->id, 'question_id' => $firstQuestion->id, 'score' => $firstScores[$index]]);
            }
            EvaluationDetail::create(['evaluation_id' => $evaluation->id, 'question_id' => $secondQuestion->id, 'score' => $secondScores[$index]]);
        }

        $response = $this->actingAs($admin)->get(route('admin.reports.evaluations', ['period' => $period->id, 'target' => $target->id]));

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->where('rows.data.0.score_percentages.5', 22)
            ->where('rows.data.0.score_percentages.4', 30)
            ->where('rows.data.0.score_percentages.3', 46)
            ->where('rows.data.0.score_percentages.2', 3)
            ->where('rows.data.0.score_percentages.1', 0));

        $csv = $this->actingAs($admin)->get(route('admin.reports.evaluations.export', ['period' => $period->id, 'target' => $target->id]));

        $csv->assertDownload('simano-rekap-evaluasi.csv');
        $this->assertStringContainsString('02/2026,', $csv->streamedContent());
        $this->assertStringContainsString('2,22%,30%,46%,3%,0%', $csv->streamedContent());
    }

    public function test_evaluation_report_exports_use_filtered_score_percentages(): void
    {
        $admin = $this->userWithRole('admin');
        $evaluator = User::factory()->create();
        $position = Position::factory()->create();
        $department = Department::factory()->create();
        $factory = Factory::factory()->create();
        $target = User::factory()->create(['name' => 'Target Export', 'nik' => 'EXPORT-001', 'position_id' => $position->id]);
        $target->departments()->sync([$department->id]);
        $target->factories()->sync([$factory->id]);
        $period = EvaluationPeriod::create(['month' => 2, 'year' => 2026, 'status' => 'active']);
        $template = EvaluationTemplate::create(['target_category' => 'atasan', 'active' => true]);
        $target->update(['evaluation_template_id' => $template->id]);
        $evaluation = Evaluation::create(['evaluator_id' => $evaluator->id, 'target_id' => $target->id, 'evaluation_template_id' => $template->id, 'evaluation_period_id' => $period->id, 'target_category' => 'atasan', 'average_score' => 4]);

        foreach ([5, 5, 4, 3, 2, 1] as $index => $score) {
            $question = Question::create(['evaluation_template_id' => $template->id, 'question_number' => $index + 1, 'question_text' => "Pertanyaan {$index}", 'active' => true]);
            EvaluationDetail::create(['evaluation_id' => $evaluation->id, 'question_id' => $question->id, 'score' => $score]);
        }

        $filters = [
            'period' => $period->id,
            'target' => $target->id,
            'position_id' => $position->id,
            'department_id' => $department->id,
            'factory_id' => $factory->id,
        ];
        $csv = $this->actingAs($admin)->get(route('admin.reports.evaluations.export', $filters));

        $csv->assertOk()->assertDownload('simano-rekap-evaluasi.csv');
        $content = $csv->streamedContent();
        $this->assertStringContainsString('"Jumlah Evaluasi","Skor 5","Skor 4","Skor 3","Skor 2","Skor 1"', $content);
        $this->assertStringContainsString('02/2026,"Target Export",EXPORT-001', $content);
        $this->assertStringContainsString('1,33%,17%,17%,17%,17%', $content);
        $this->assertStringNotContainsString('Rata-rata', $content);

        $this->actingAs($admin)->get(route('admin.reports.evaluations.export.pdf', $filters))
            ->assertOk()
            ->assertDownload('simano-rekap-evaluasi.pdf');
    }

    public function test_evaluation_report_returns_zero_percentages_without_answers(): void
    {
        $admin = $this->userWithRole('admin');
        $evaluator = User::factory()->create();
        $target = User::factory()->create();
        $period = EvaluationPeriod::create(['month' => 2, 'year' => 2026, 'status' => 'active']);
        $template = EvaluationTemplate::create(['target_category' => 'atasan', 'active' => true]);
        $target->update(['evaluation_template_id' => $template->id]);
        Evaluation::create(['evaluator_id' => $evaluator->id, 'target_id' => $target->id, 'evaluation_template_id' => $template->id, 'evaluation_period_id' => $period->id, 'target_category' => 'atasan', 'average_score' => 0]);

        $this->actingAs($admin)->get(route('admin.reports.evaluations', ['period' => $period->id, 'target' => $target->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('rows.data.0.score_percentages.5', 0)
                ->where('rows.data.0.score_percentages.4', 0)
                ->where('rows.data.0.score_percentages.3', 0)
                ->where('rows.data.0.score_percentages.2', 0)
                ->where('rows.data.0.score_percentages.1', 0));
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

    public function test_admin_can_reset_evaluations_by_period(): void
    {
        $admin = $this->userWithRole('admin');
        $period = EvaluationPeriod::create(['month' => 9, 'year' => 2026, 'status' => 'active']);
        $otherPeriod = EvaluationPeriod::create(['month' => 8, 'year' => 2026, 'status' => 'closed']);
        $template = EvaluationTemplate::create(['target_category' => 'atasan', 'active' => true]);
        $target = User::factory()->create();
        $evaluator = User::factory()->create();
        $evaluation = Evaluation::create(['evaluator_id' => $evaluator->id, 'target_id' => $target->id, 'evaluation_template_id' => $template->id, 'evaluation_period_id' => $period->id, 'target_category' => 'atasan', 'average_score' => 4]);
        $other = Evaluation::create(['evaluator_id' => User::factory()->create()->id, 'target_id' => $target->id, 'evaluation_template_id' => $template->id, 'evaluation_period_id' => $otherPeriod->id, 'target_category' => 'atasan', 'average_score' => 5]);

        $this->actingAs($admin)->post(route('admin.settings.evaluation-reset.store'), ['scope' => 'period', 'period_id' => $period->id])
            ->assertRedirect();

        $this->assertDatabaseMissing('evaluations', ['id' => $evaluation->id]);
        $this->assertDatabaseHas('evaluations', ['id' => $other->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'evaluations.reset']);
    }

    public function test_admin_or_hr_can_delete_one_atasan_evaluation(): void
    {
        $hr = $this->userWithRole('hr');
        $evaluator = User::factory()->create();
        $otherEvaluator = User::factory()->create();
        $target = User::factory()->create();
        $period = EvaluationPeriod::create(['month' => 9, 'year' => 2026, 'status' => 'active']);
        $template = EvaluationTemplate::create(['target_category' => 'atasan', 'active' => true]);
        $evaluation = Evaluation::create(['evaluator_id' => $evaluator->id, 'target_id' => $target->id, 'evaluation_template_id' => $template->id, 'evaluation_period_id' => $period->id, 'target_category' => 'atasan', 'average_score' => 4]);
        $other = Evaluation::create(['evaluator_id' => $otherEvaluator->id, 'target_id' => $target->id, 'evaluation_template_id' => $template->id, 'evaluation_period_id' => $period->id, 'target_category' => 'atasan', 'average_score' => 5]);

        $this->actingAs($hr)->delete(route('admin.reports.evaluations.atasan.evaluators.destroy', [$period, $target, $evaluation]))
            ->assertRedirect(route('admin.reports.evaluations.atasan.evaluators', ['period' => $period->id, 'target' => $target->id]));

        $this->assertDatabaseMissing('evaluations', ['id' => $evaluation->id]);
        $this->assertDatabaseHas('evaluations', ['id' => $other->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'evaluation.deleted']);
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

        $pending = User::query()->where('nik', 'NIK-PENDING-001')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.pending-users.index'))
            ->assertOk()
            ->assertSee('NIK-PENDING-001')
            ->assertSee('MANAGER');

        $this->actingAs($admin)->get(route('admin.pending-users.edit', $pending))
            ->assertOk()
            ->assertSee('NIK-PENDING-001');
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
