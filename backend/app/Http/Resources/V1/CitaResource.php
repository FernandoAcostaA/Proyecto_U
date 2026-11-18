<?php

namespace App\Http\Resources\V1;

use App\Models\Cita;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CitaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cliente' => new ClienteResource($this->whenLoaded('cliente')),
            'empleado' => new EmpleadoResource($this->whenLoaded('empleado')),
            'servicio' => new ServicioResource($this->whenLoaded('servicio')),
            'tipo_corte' => new TipoCorteResource($this->whenLoaded('tipoCorte')),
            'fecha_hora_inicio' => $this->fecha_hora_inicio?->toIso8601String(),
            'fecha_hora_fin' => $this->fecha_hora_fin?->toIso8601String(),
            'estado' => $this->estado,
            'calificacion' => new CalificacionResource($this->whenLoaded('calificacion')),
            'calificacion_token' => $this->calificacion_token,
            'enlace_calificacion' => $this->when(
                $this->estado === Cita::ESTADO_COMPLETADA && $this->calificacion_token,
                fn () => url('api/v1/calificaciones/publica/'.$this->calificacion_token)
            ),
            'comision' => $this->whenLoaded('comision', fn () => [
                'id' => $this->comision?->id,
                'monto' => $this->comision?->monto,
                'porcentaje_barbero' => $this->comision?->porcentaje_barbero,
                'porcentaje_barberia' => $this->comision?->porcentaje_barberia,
                'monto_barberia' => $this->comision?->monto_barberia,
                'estado_pago' => $this->comision?->estado_pago,
            ]),
            'pago' => $this->whenLoaded('pago', fn () => [
                'id' => $this->pago?->id,
                'monto' => $this->pago?->monto,
                'metodo' => $this->pago?->metodo,
                'estado' => $this->pago?->estado,
                'pagado_en' => $this->pago?->pagado_en?->toIso8601String(),
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
