<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CierreCajaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fecha' => $this->fecha?->toDateString(),
            'cantidad_pagos' => $this->cantidad_pagos,
            'cantidad_citas' => $this->cantidad_citas,
            'total_cobrado' => $this->total_cobrado,
            'total_comisiones' => $this->total_comisiones,
            'total_barberia' => $this->total_barberia,
            'total_pendiente_comisiones' => $this->total_pendiente_comisiones,
            'detalle' => $this->detalle,
            'notas' => $this->notas,
            'cerrado_por' => new UsuarioResource($this->whenLoaded('cerradoPor')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
