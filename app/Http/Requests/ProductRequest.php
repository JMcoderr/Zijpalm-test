<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $prices = [];

        foreach (['price', 'guest_price'] as $field) {
            $value = $this->input($field);
            $prices[$field] = is_string($value) ? str_replace(',', '.', trim($value)) : $value;
        }

        $this->merge($prices);
    }

    public function rules(): array
    {
        $activityOnly = Rule::excludeIf(fn () => $this->input('product_type') !== 'activity');

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'product_type' => ['required', Rule::in(['product', 'activity'])],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0'],
            'guest_price' => ['nullable', 'numeric', 'decimal:0,2', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'max_participants' => [$activityOnly, 'nullable', 'integer', 'min:1'],
            'pickupLocation' => ['nullable', 'string', 'max:255'],
            'allow_guest_checkout' => ['nullable', 'boolean'],
            'hidden' => ['nullable', 'boolean'],
            'imagePath' => ['nullable', 'image', 'max:5120'],
            'registration_starts_at' => [$activityOnly, 'nullable', 'date'],
            'registration_ends_at' => [$activityOnly, 'nullable', 'date', 'after_or_equal:registration_starts_at'],
            'activity_starts_at' => [$activityOnly, 'nullable', 'date'],
            'activity_ends_at' => [$activityOnly, 'nullable', 'date', 'after_or_equal:activity_starts_at'],
        ];
    }

    public function messages(): array
    {
        return [
            'price.numeric' => 'Vul een geldige ledenprijs in, bijvoorbeeld 15,50 of 15.50.',
            'guest_price.numeric' => 'Vul een geldige niet-ledenprijs in, bijvoorbeeld 17,50 of 17.50.',
            'price.decimal' => 'De ledenprijs mag maximaal twee decimalen hebben.',
            'guest_price.decimal' => 'De niet-ledenprijs mag maximaal twee decimalen hebben.',
            'allow_guest_checkout.boolean' => 'Het veld :attribute moet waar of onwaar zijn.',
            'hidden.boolean' => 'Het veld :attribute moet waar of onwaar zijn.',
        ];
    }

    public function attributes(): array
    {
        return [
            'price' => 'ledenprijs',
            'guest_price' => 'niet-ledenprijs',
            'allow_guest_checkout' => 'niet-leden mogen ook bestellen',
            'hidden' => 'verborgen item',
        ];
    }
}
