<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'gte:min_price'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'max_stock' => ['nullable', 'integer', 'min:0', 'gte:min_stock'],
            'low_stock' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'in:name,price,quantity,created_at'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['low_stock', 'is_active'] as $field) {
            if (! $this->has($field) || ! is_string($this->input($field))) {
                continue;
            }

            $value = filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($value !== null) {
                $normalized[$field] = $value;
            }
        }

        $this->merge($normalized);
    }
}
