<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'product_type' => ['required', Rule::in(['product', 'activity'])],
            'price' => ['required', 'numeric', 'min:0'],
            'guest_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'max_participants' => ['nullable', 'integer', 'min:1'],
            'pickupLocation' => ['nullable', 'string', 'max:255'],
            'allow_guest_checkout' => ['nullable', 'boolean'],
            'hidden' => ['nullable', 'boolean'],
            'imagePath' => ['nullable', 'image', 'max:5120'],
            'registration_starts_at' => ['nullable', 'date'],
            'registration_ends_at' => ['nullable', 'date', 'after_or_equal:registration_starts_at'],
            'activity_starts_at' => ['nullable', 'date'],
            'activity_ends_at' => ['nullable', 'date', 'after_or_equal:activity_starts_at'],
        ];
    }

    public function messages(): array
    {
        return [
            'allow_guest_checkout.boolean' => 'Het veld :attribute moet waar of onwaar zijn.',
            'hidden.boolean' => 'Het veld :attribute moet waar of onwaar zijn.',
        ];
    }

    public function attributes(): array
    {
        return [
            'allow_guest_checkout' => 'niet-leden mogen ook bestellen',
            'hidden' => 'verborgen item',
        ];
    }
}
