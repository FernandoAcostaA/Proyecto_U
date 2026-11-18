<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmpleadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('patch') ? 'sometimes' : 'required';

        return [
            'nombre' => [$required, 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'comision_base' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fecha_ingreso' => [$required, 'date'],
            'usuario_id' => ['nullable', 'integer', 'exists:usuarios,id'],
            'servicios' => ['sometimes', 'array'],
            'servicios.*' => ['integer', 'exists:servicios,id'],
            'horarios' => ['sometimes', 'array'],
            'horarios.*.dia_semana' => ['required', 'integer', 'between:0,6'],
            'horarios.*.hora_inicio' => ['required', 'date_format:H:i'],
            'horarios.*.hora_fin' => ['required', 'date_format:H:i', 'after:horarios.*.hora_inicio'],
        ];
    }
}
