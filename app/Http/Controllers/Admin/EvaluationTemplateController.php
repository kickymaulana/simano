<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EvaluationTemplateRequest;
use App\Models\EvaluationTemplate;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EvaluationTemplateController extends Controller
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function index(): Response
    {
        return Inertia::render('Admin/Templates/Index', [
            'templates' => EvaluationTemplate::withCount('questions')->orderBy('target_category')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Templates/Form', ['template' => null]);
    }

    public function store(EvaluationTemplateRequest $request): RedirectResponse
    {
        $template = DB::transaction(function () use ($request): EvaluationTemplate {
            $data = $request->validated();
            $questions = $data['questions'];
            unset($data['questions']);

            $template = EvaluationTemplate::create($data);
            $template->questions()->createMany($questions);

            return $template;
        });

        $this->audit->record('evaluation_template.created', $template, [
            'target_category' => $template->target_category,
            'question_count' => $template->questions()->count(),
        ]);

        return to_route('admin.evaluation-templates.index')->with('success', 'Template evaluasi dibuat.');
    }

    public function edit(EvaluationTemplate $evaluationTemplate): Response
    {
        return Inertia::render('Admin/Templates/Form', [
            'template' => $evaluationTemplate->load('questions'),
        ]);
    }

    public function update(EvaluationTemplateRequest $request, EvaluationTemplate $evaluationTemplate): RedirectResponse
    {
        abort_if($evaluationTemplate->evaluations()->exists(), 409, 'Template sudah dipakai evaluasi dan tidak dapat diubah.');

        DB::transaction(function () use ($request, $evaluationTemplate): void {
            $data = $request->validated();
            $questions = $data['questions'];
            unset($data['questions']);

            $evaluationTemplate->update($data);
            $evaluationTemplate->questions()->delete();
            $evaluationTemplate->questions()->createMany($questions);
        });

        $this->audit->record('evaluation_template.updated', $evaluationTemplate->fresh(), [
            'target_category' => $evaluationTemplate->target_category,
            'question_count' => $evaluationTemplate->questions()->count(),
        ]);

        return to_route('admin.evaluation-templates.index')->with('success', 'Template evaluasi diperbarui.');
    }

    public function destroy(EvaluationTemplate $evaluationTemplate): RedirectResponse
    {
        abort_if($evaluationTemplate->evaluations()->exists(), 409, 'Template sudah dipakai evaluasi dan tidak dapat dihapus.');

        $snapshot = $evaluationTemplate->only(['target_category', 'active']);
        $evaluationTemplate->delete();
        $this->audit->record('evaluation_template.deleted', null, $snapshot);

        return to_route('admin.evaluation-templates.index')->with('success', 'Template evaluasi dihapus.');
    }
}
