<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Pago;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EstadoPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', Rule::in(Pago::estados())],
            'metodo' => ['nullable', Rule::in(Pago::metodos())],
        ];
    }
}
