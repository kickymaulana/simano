<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UserAdminRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'role' => ['required', 'string', 'in:employee,hr,admin'],
            'position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'department_ids' => ['sometimes', 'array'],
            'department_ids.*' => ['integer', 'exists:departments,id'],
            'factory_ids' => ['sometimes', 'array'],
            'factory_ids.*' => ['integer', 'exists:factories,id'],
        ];
    }
}
