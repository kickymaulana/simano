<?php

namespace App\Http\Requests\Employee;

use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use Illuminate\Foundation\Http\FormRequest;

class StoreEvaluationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['employee', 'admin', 'hr']) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'target_id' => ['required', 'integer', 'exists:users,id'],
            'template_id' => ['required', 'integer', 'exists:evaluation_templates,id'],
            'scores' => ['required', 'array', 'min:1'],
            'scores.*' => ['required', 'integer', 'between:1,5'],
        ];
    }
}
