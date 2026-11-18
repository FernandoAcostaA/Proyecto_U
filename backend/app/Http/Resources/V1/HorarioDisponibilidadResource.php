<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HorarioDisponibilidadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'empleado_id' => $this->empleado_id,
            'dia_semana' => $this->dia_semana,
            'dia' => $this->dia_semana_nombre,
            'hora_inicio' => $this->hora_inicio,
            'hora_fin' => $this->hora_fin,
        ];
    }
}
