<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EvaluationReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'hr']) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'period' => ['nullable', 'integer', 'exists:evaluation_periods,id'],
            'target' => ['nullable', 'integer', 'exists:users,id'],
            'position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'factory_id' => ['nullable', 'integer', 'exists:factories,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'group_by' => ['nullable', 'string', 'in:department,factory,position'],
            'threshold' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50'],
        ];
    }
}
