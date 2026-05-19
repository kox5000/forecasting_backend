<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaleUpdateRequest extends FormRequest
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
            'product_id' => 'sometimes|required|exists:products,id',
            'region' => 'sometimes|required|string|max:255',
            'date' => 'sometimes|required|date',
            'quantity' => 'sometimes|required|integer|min:0',
            'revenue' => 'sometimes|required|numeric|min:0',
        ];
    }
}
