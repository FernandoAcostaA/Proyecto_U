<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Cita;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EstadoCitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', Rule::in([
                Cita::ESTADO_PENDIENTE,
                Cita::ESTADO_CONFIRMADA,
                Cita::ESTADO_CANCELADA,
                Cita::ESTADO_NO_ASISTIO,
            ])],
        ];
    }
}
