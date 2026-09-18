<?php

namespace App\Http\Requests\Employee;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'position_id' => ['required', 'integer', 'exists:positions,id'],
            'department_ids' => ['required', 'array', 'min:1'],
            'department_ids.*' => ['integer', 'distinct', 'exists:departments,id'],
            'factory_ids' => ['required', 'array', 'min:1'],
            'factory_ids.*' => ['integer', 'distinct', 'exists:factories,id'],
        ];
    }
}
