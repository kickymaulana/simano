<?php

namespace Tests\Feature;

use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationTemplate;
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

    public function test_approving_pending_user_syncs_spatie_role_and_clears_request(): void
    {
        $admin = $this->userWithRole('admin');
        Role::firstOrCreate(['name' => 'hr', 'guard_name' => 'web']);
        $pending = User::factory()->create([
            'is_approved' => false,
            'requested_role' => 'hr',
        ]);

        $this->actingAs($admin)->post(route('admin.pending-users.approve', $pending))
            ->assertRedirect();

        $pending->refresh();

        $this->assertTrue($pending->is_approved);
        $this->assertTrue($pending->active);
        $this->assertNull($pending->requested_role);
        $this->assertEquals('hr', $pending->role);
        $this->assertTrue($pending->hasRole('hr'));
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        $user->assignRole($role);

        return $user;
    }
}
