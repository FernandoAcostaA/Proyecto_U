<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class ReporteFechasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'desde' => ['nullable', 'date', 'before_or_equal:hasta'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'empleado_id' => ['nullable', 'integer', 'exists:empleados,id'],
            'servicio_id' => ['nullable', 'integer', 'exists:servicios,id'],
        ];
    }

    /**
     * Resuelve el rango consultado (por defecto los últimos 30 días).
     */
    public function rango(): array
    {
        $hasta = $this->filled('hasta')
            ? Carbon::parse($this->input('hasta'))->endOfDay()
            : Carbon::today()->endOfDay();

        $desde = $this->filled('desde')
            ? Carbon::parse($this->input('desde'))->startOfDay()
            : $hasta->copy()->subDays(29)->startOfDay();

        return [
            'desde' => $desde,
            'hasta' => $hasta,
        ];
    }

    public function empleadoId(): ?int
    {
        return $this->integer('empleado_id') ?: null;
    }

    public function servicioId(): ?int
    {
        return $this->integer('servicio_id') ?: null;
    }
}
