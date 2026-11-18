<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ReprogramarCitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha_hora_inicio' => ['required', 'date_format:Y-m-d H:i:s', 'after:now'],
            'empleado_id' => ['nullable', 'integer', 'exists:empleados,id'],
            'servicio_id' => ['nullable', 'integer', 'exists:servicios,id'],
            'tipo_corte_id' => ['nullable', 'integer', 'exists:tipos_corte,id'],
        ];
    }
}
