<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cita_id' => $this->cita_id,
            'tipo' => $this->tipo,
            'canal' => $this->canal,
            'destino' => $this->destino,
            'estado' => $this->estado,
            'proveedor' => $this->proveedor,
            'costo_estimado' => $this->costo_estimado,
            'asunto' => $this->asunto,
            'mensaje' => $this->mensaje,
            'error' => $this->error,
            'id_externo' => $this->id_externo,
            'respuesta' => $this->respuesta,
            'respondido_en' => $this->respondido_en?->toIso8601String(),
            'fecha_envio_programada' => $this->fecha_envio_programada?->toIso8601String(),
            'fecha_envio_real' => $this->fecha_envio_real?->toIso8601String(),
            'metadata' => $this->metadata,
            'cita' => new CitaResource($this->whenLoaded('cita')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
