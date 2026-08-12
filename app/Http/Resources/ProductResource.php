<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'reorder_level' => $this->reorder_level,
            'is_low_stock' => $this->quantity <= $this->reorder_level,
            'is_active' => $this->is_active,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'suppliers' => SupplierResource::collection($this->whenLoaded('suppliers')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
