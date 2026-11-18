<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'rol_id' => $this->rol_id,
            'rol' => $this->rol?->nombre,
            'rol_descripcion' => $this->rol?->descripcion,
            'cliente' => new ClienteResource($this->whenLoaded('cliente')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
