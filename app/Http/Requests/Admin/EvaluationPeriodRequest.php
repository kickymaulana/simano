<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EvaluationPeriodRequest extends FormRequest
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
        $periodId = $this->route('evaluation_period')?->id;

        return [
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => [
                'required',
                'integer',
                'between:2020,2100',
                Rule::unique('evaluation_periods')->where(fn ($query) => $query->where('month', $this->integer('month')))->ignore($periodId),
            ],
            'status' => ['required', Rule::in(['draft', 'active', 'closed'])],
            'opens_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date', 'after_or_equal:opens_at'],
        ];
    }
}
