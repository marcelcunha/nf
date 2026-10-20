<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNfePricingRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'prices' => ['required', 'array'],
            'prices.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'prices.*.sale_price' => ['nullable', 'numeric', 'min:0', 'max:99999999999'],
        ];
    }
}
