<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalificacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cita_id' => $this->cita_id,
            'empleado_id' => $this->empleado_id,
            'servicio_id' => $this->servicio_id,
            'estrellas' => $this->estrellas,
            'comentario' => $this->comentario,
            'origen' => $this->origen,
            'empleado' => new EmpleadoResource($this->whenLoaded('empleado')),
            'servicio' => new ServicioResource($this->whenLoaded('servicio')),
            'cita' => new CitaResource($this->whenLoaded('cita')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
