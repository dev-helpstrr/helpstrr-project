<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;

class CustomerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => 'required|string|max:30',
            'name' => 'nullable|string|max:191',
            'email' => 'nullable|email|max:191',
            'profile_photo' => 'nullable|image|max:5120',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'addresses' => 'nullable|array',
            'addresses.*.type' => 'nullable|string|max:50',
            'addresses.*.address_line_1' => 'nullable|string|max:255',
            'addresses.*.address_line_2' => 'nullable|string|max:255',
            'addresses.*.city' => 'nullable|string|max:120',
            'addresses.*.state' => 'nullable|string|max:120',
            'addresses.*.pincode' => 'nullable|string|max:20',
            'addresses.*.country' => 'nullable|string|max:120',
            'addresses.*.latitude' => 'nullable|numeric',
            'addresses.*.longitude' => 'nullable|numeric',
        ];
    }
}
