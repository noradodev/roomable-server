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
            'property' => 'required|array',
            'property.name' => 'required|string|max:255',
            'property.address' => 'required|string|max:255',
            'property.city' => 'required|string|max:255',
            'property.description' => 'nullable|string',
            'property.image_url' => 'nullable|string',

            'props_image' => 'nullable|file|image|max:5120', 

            'roomSetup' => 'required|array',
            'roomSetup.floors' => 'required|array|min:1',

            'roomSetup.floors.*.name' => 'required|string|max:255',
            'roomSetup.floors.*.number' => 'required|numeric',

            'roomSetup.floors.*.rooms' => 'required|array|min:1',
            'roomSetup.floors.*.rooms.*.roomNumber' => 'required|string|max:255',
            'roomSetup.floors.*.rooms.*.type' => 'required|string|max:255',
            'roomSetup.floors.*.rooms.*.price' => 'required|numeric|min:0',
        ];
    }
}
