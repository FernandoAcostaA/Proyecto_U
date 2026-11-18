<?php

namespace App\Services\Pagos;

use App\Models\Cita;
use App\Models\Pago;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Registro del ciclo de vida de los pagos de citas.
 * La cita al completarse genera un pago automatico; tambien se permite
 * registrar pagos manuales y cambiar su estado (reembolso).
 */
class PagoService
{
    /**
     * Genera el pago automatico al completar la cita.
     */
    public function registrarAutomatico(Cita $cita, string $metodo = Pago::METODO_EFECTIVO): Pago
    {
        return Pago::create([
            'cita_id' => $cita->id,
            'monto' => $cita->servicio->precio,
            'metodo' => $metodo,
            'estado' => Pago::ESTADO_PAGADO,
            'pagado_en' => now(),
        ]);
    }

    /**
     * Registra de forma manual el pago de una cita ya completada.
     */
    public function registrar(array $data): Pago
    {
        $cita = Cita::findOrFail($data['cita_id']);

        if ($cita->estado !== Cita::ESTADO_COMPLETADA) {
            throw ValidationException::withMessages([
                'cita_id' => 'Solo se puede registrar el pago de una cita completada.',
            ]);
        }

        if ($cita->pago) {
            throw ValidationException::withMessages([
                'cita_id' => 'La cita ya tiene un pago registrado.',
            ]);
        }

        return Pago::create([
            'cita_id' => $cita->id,
            'monto' => $data['monto'] ?? $cita->servicio->precio,
            'metodo' => $data['metodo'],
            'estado' => Pago::ESTADO_PAGADO,
            'pagado_en' => now(),
        ]);
    }

    public function cambiarEstado(Pago $pago, string $estado, ?string $metodo = null): Pago
    {
        DB::transaction(function () use ($pago, $estado, $metodo) {
            $pago->update([
                'estado' => $estado,
                'metodo' => $metodo ?? $pago->metodo,
                'pagado_en' => $estado === Pago::ESTADO_PAGADO ? now() : $pago->pagado_en,
            ]);
        });

        return $pago->fresh();
    }
}
