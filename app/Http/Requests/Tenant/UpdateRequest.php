<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = $this->route('tenant')?->id;
        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:tenants,email,' . $tenantId,
            'phone' => 'sometimes|string|max:50',
            'status' => 'sometimes|in:active,inactive',
            'due_date' => 'sometimes|date',
            'rent_status' => 'nullable|in:on_time,due_soon,over_due',
            'move_in_date' => 'nullable|date',
            'move_out_date' => 'nullable|date|after_or_equal:move_in_date',
            'notes' => 'nullable|string|max:500',
        ];
    }
}
