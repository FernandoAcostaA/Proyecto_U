<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreClienteRequest extends FormRequest
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
            'telefono' => ['nullable', 'string', 'max:20', 'unique:clientes,telefono'],
            'email' => ['nullable', 'email', 'max:255', 'unique:clientes,email'],
            'consentimiento' => ['nullable', 'boolean'],
        ];
    }
}
