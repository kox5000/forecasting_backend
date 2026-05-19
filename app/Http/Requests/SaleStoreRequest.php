<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaleStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'region' => 'required|string|max:255',
            'date' => 'required|date',
            'quantity' => 'required|integer|min:0',
            'revenue' => 'required|numeric|min:0',
        ];
    }
}
