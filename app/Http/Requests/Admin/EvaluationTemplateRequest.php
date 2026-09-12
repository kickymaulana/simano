<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EvaluationTemplateRequest extends FormRequest
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
        $templateId = $this->route('evaluation_template')?->id;

        return [
            'target_category' => [
                'required',
                'string',
                'max:100',
                Rule::unique('evaluation_templates', 'target_category')->ignore($templateId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'active' => ['sometimes', 'boolean'],
            'questions' => ['required', 'array', 'min:1', 'max:50'],
            'questions.*.question_number' => ['required', 'integer', 'min:1', 'max:50'],
            'questions.*.question_text' => ['required', 'string', 'max:1000'],
            'questions.*.active' => ['sometimes', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function ($validator): void {
            $numbers = collect($this->input('questions', []))->pluck('question_number');

            if ($numbers->duplicates()->isNotEmpty()) {
                $validator->errors()->add('questions', 'Nomor pertanyaan tidak boleh duplikat.');
            }
        }];
    }
}
