<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Pago;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cita_id' => ['required', 'integer', 'exists:citas,id'],
            'metodo' => ['required', Rule::in(Pago::metodos())],
            'monto' => ['nullable', 'numeric', 'min:0.01'],
        ];
    }
}
