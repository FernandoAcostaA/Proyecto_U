<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmpleadoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'usuario_id' => $this->usuario_id,
            'nombre' => $this->nombre,
            'telefono' => $this->telefono,
            'foto' => $this->fotoUrl(),
            'comision_base' => $this->comision_base,
            'fecha_ingreso' => $this->fecha_ingreso?->toDateString(),
            'calificacion_promedio' => $this->calificacionPromedio(),
            'total_calificaciones' => $this->totalCalificaciones(),
            'servicios' => ServicioResource::collection($this->whenLoaded('servicios')),
            'horarios' => HorarioDisponibilidadResource::collection($this->whenLoaded('horariosDisponibilidad')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    protected function calificacionPromedio(): ?float
    {
        if (! array_key_exists('calificaciones_avg_estrellas', $this->resource->getAttributes())) {
            return null;
        }

        return round((float) ($this->calificaciones_avg_estrellas ?? 0), 2);
    }

    protected function totalCalificaciones(): ?int
    {
        if (! array_key_exists('calificaciones_count', $this->resource->getAttributes())) {
            return null;
        }

        return (int) $this->calificaciones_count;
    }
}
