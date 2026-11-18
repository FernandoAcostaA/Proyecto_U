<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreServicioRequest extends FormRequest
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
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'duracion_min' => [$required, 'integer', 'min:5', 'max:1440'],
            'precio' => [$required, 'numeric', 'min:0'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'porcentaje_comision' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'empleados' => ['sometimes', 'array'],
            'empleados.*' => ['integer', 'exists:empleados,id'],
            'productos' => ['sometimes', 'array'],
            'productos.*.producto_id' => ['required', 'integer', 'exists:productos,id'],
            'productos.*.cantidad_usada' => ['required', 'integer', 'min:1'],
        ];
    }
}
