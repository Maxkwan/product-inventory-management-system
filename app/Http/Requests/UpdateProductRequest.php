<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'sku' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('products')->ignore($this->route('product'))],
            'description' => ['sometimes', 'nullable', 'string'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0', 'decimal:0,2'],
            'quantity' => ['sometimes', 'integer', 'min:0'],
            'reorder_level' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'supplier_ids' => ['sometimes', 'array'],
            'supplier_ids.*' => ['integer', 'distinct', 'exists:suppliers,id'],
        ];
    }
}
