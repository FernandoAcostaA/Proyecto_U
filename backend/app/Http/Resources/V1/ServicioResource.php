<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ServicioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'duracion_min' => $this->duracion_min,
            'precio' => $this->precio,
            'imagen' => $this->imagen,
            'imagen_url' => $this->imagen ? Storage::disk('public')->url($this->imagen) : null,
            'porcentaje_comision' => $this->porcentaje_comision,
            'calificacion_promedio' => $this->calificacionPromedio(),
            'total_calificaciones' => $this->totalCalificaciones(),
            'empleados' => EmpleadoResource::collection($this->whenLoaded('empleados')),
            'productos' => ProductoResource::collection($this->whenLoaded('productos')),
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
