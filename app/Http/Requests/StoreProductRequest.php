<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'quantity' => ['sometimes', 'integer', 'min:0'],
            'reorder_level' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'supplier_ids' => ['sometimes', 'array'],
            'supplier_ids.*' => ['integer', 'distinct', 'exists:suppliers,id'],
        ];
    }
}
