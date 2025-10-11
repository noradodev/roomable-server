<?php

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
        return [
            'landlord_id' => 'uuid|exists:users,id',
            'name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'image_url' => 'nullable|string',
            'description' => 'nullable|string',
            'floors' => 'required|array',
            'floors.*.name' => 'required|string',
            'floors.*.floor_number' => 'required|numeric',
            'floors.*.rooms' => 'required|array',
            'floors.*.rooms.*.room_number' => 'required|string',
            'floors.*.rooms.*.room_type' => 'required|string',
            'floors.*.rooms.*.price' => 'required|numeric|min:0',
        ];
    }
}
