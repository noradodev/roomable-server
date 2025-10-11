<?php

namespace App\Http\Requests\Room;

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
        return [
            'floor_id' => 'sometimes|uuid|exists:floors,id',
            'room_number' => 'sometimes|string|max:50',
            'room_type' => 'sometimes|string|max:100',
            'price' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:available,occupied,maintenance'
        ];
    }
}
