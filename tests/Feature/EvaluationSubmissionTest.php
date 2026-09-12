<?php

namespace Tests\Feature;

use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationTemplate;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EvaluationSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_evaluation_submission_stores_average_and_details(): void
    {
        $evaluator = User::factory()->create(['nik' => 'NIK-001', 'active' => true]);
        $target = User::factory()->create(['nik' => 'NIK-002', 'active' => true]);
        Role::create(['name' => 'employee', 'guard_name' => 'web']);
        $evaluator->assignRole('employee');
        $period = EvaluationPeriod::create(['month' => 9, 'year' => 2026, 'status' => 'active']);
        $template = EvaluationTemplate::create(['target_category' => 'atasan', 'active' => true]);
        $first = Question::create(['evaluation_template_id' => $template->id, 'question_number' => 1, 'question_text' => 'Satu', 'active' => true]);
        $second = Question::create(['evaluation_template_id' => $template->id, 'question_number' => 2, 'question_text' => 'Dua', 'active' => true]);

        $response = $this->actingAs($evaluator)->post(route('evaluations.store'), [
            'target_id' => $target->id,
            'template_id' => $template->id,
            'scores' => [$first->id => 2, $second->id => 4],
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('evaluations', [
            'evaluator_id' => $evaluator->id,
            'target_id' => $target->id,
            'evaluation_period_id' => $period->id,
            'average_score' => '3.00',
        ]);
        $this->assertDatabaseCount('evaluation_details', 2);
    }

    public function test_database_constraint_rejects_duplicate_evaluator_target_period(): void
    {
        $evaluator = User::factory()->create(['nik' => 'NIK-005']);
        $target = User::factory()->create(['nik' => 'NIK-006']);
        $period = EvaluationPeriod::create(['month' => 10, 'year' => 2026, 'status' => 'active']);
        $template = EvaluationTemplate::create(['target_category' => 'atasan', 'active' => true]);
        $attributes = [
            'evaluator_id' => $evaluator->id,
            'target_id' => $target->id,
            'evaluation_template_id' => $template->id,
            'evaluation_period_id' => $period->id,
            'target_category' => 'atasan',
            'average_score' => 4,
        ];

        Evaluation::create($attributes);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        Evaluation::create($attributes);
    }

    public function test_admin_can_export_aggregate_pdf_without_evaluator_identity(): void
    {
        $admin = User::factory()->create(['nik' => 'NIK-007']);
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.reports.evaluations.export.pdf'));

        $response->assertDownload('simano-rekap-evaluasi.pdf');
    }

    public function test_duplicate_submission_is_rejected_without_new_evaluation(): void
    {
        $evaluator = User::factory()->create(['nik' => 'NIK-003', 'active' => true]);
        $target = User::factory()->create(['nik' => 'NIK-004', 'active' => true]);
        Role::create(['name' => 'employee', 'guard_name' => 'web']);
        $evaluator->assignRole('employee');
        $period = EvaluationPeriod::create(['month' => 9, 'year' => 2026, 'status' => 'active']);
        $template = EvaluationTemplate::create(['target_category' => 'atasan', 'active' => true]);
        $question = Question::create(['evaluation_template_id' => $template->id, 'question_number' => 1, 'question_text' => 'Satu', 'active' => true]);
        Evaluation::create([
            'evaluator_id' => $evaluator->id,
            'target_id' => $target->id,
            'evaluation_template_id' => $template->id,
            'evaluation_period_id' => $period->id,
            'target_category' => 'atasan',
            'average_score' => 5,
        ]);

        $response = $this->actingAs($evaluator)->post(route('evaluations.store'), [
            'target_id' => $target->id,
            'template_id' => $template->id,
            'scores' => [$question->id => 5],
        ]);

        $response->assertRedirect(route('targets.index'));
        $response->assertSessionHasErrors('evaluation');
        $this->assertDatabaseCount('evaluations', 1);
    }
}
