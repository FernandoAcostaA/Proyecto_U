<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTipoCorteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('patch') || $this->isMethod('put') ? 'sometimes' : 'required';

        return [
            'nombre' => [
                $required,
                'string',
                'max:255',
                Rule::unique('tipos_corte', 'nombre')
                    ->ignore($this->route('tipoCorte')?->id),
            ],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }
}
