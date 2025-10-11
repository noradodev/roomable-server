<?php

namespace App\Http\Requests\Room;

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
            'floor_id' => 'required|uuid|exists:floors,id',
            'room_number' => 'required|string|max:50',
            'room_type' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'status' => 'nullable|in:available,occupied,maintenance',
        ];
    }
}
