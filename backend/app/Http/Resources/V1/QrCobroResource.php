<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QrCobroResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cita_id' => $this->cita_id,
            'pago_id' => $this->pago_id,
            'referencia' => $this->referencia,
            'monto' => $this->monto,
            'estado' => $this->estado,
            'payload' => $this->payload,
            'expira_en' => $this->expira_en?->toIso8601String(),
            'confirmado_en' => $this->confirmado_en?->toIso8601String(),
            'metadata' => $this->metadata,
            'imagen_url' => route('qr-cobros.imagen', ['qrCobro' => $this->id]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
