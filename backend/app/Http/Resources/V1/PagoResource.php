<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PagoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cita' => $this->whenLoaded('cita', fn () => [
                'id' => $this->cita?->id,
                'cliente' => $this->cita?->cliente?->nombre,
                'servicio' => $this->cita?->servicio?->nombre,
            ]),
            'monto' => $this->monto,
            'metodo' => $this->metodo,
            'estado' => $this->estado,
            'pagado_en' => $this->pagado_en?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
