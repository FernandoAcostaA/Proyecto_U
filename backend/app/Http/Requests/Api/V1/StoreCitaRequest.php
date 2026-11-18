<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreCitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'empleado_id' => ['required', 'integer', 'exists:empleados,id'],
            'servicio_id' => ['required', 'integer', 'exists:servicios,id'],
            'tipo_corte_id' => ['nullable', 'integer', 'exists:tipos_corte,id'],
            'fecha_hora_inicio' => ['required', 'date_format:Y-m-d H:i:s', 'after:now'],
        ];
    }
}
