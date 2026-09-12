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
            'category' => ['nullable', 'string', 'max:100'],
            'target' => ['nullable', 'integer', 'exists:users,id'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50'],
        ];
    }
}
