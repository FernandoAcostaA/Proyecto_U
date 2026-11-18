<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'proveedor_id' => $this->proveedor_id,
            'proveedor' => $this->whenLoaded('proveedor', fn () => $this->proveedor?->nombre),
            'nombre' => $this->nombre,
            'stock_actual' => $this->stock_actual,
            'stock_minimo' => $this->stock_minimo,
            'stock_bajo' => $this->tieneStockBajo(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
