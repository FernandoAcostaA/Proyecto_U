<?php

namespace App\Services\Calificaciones;

use App\Models\Calificacion;
use App\Models\Cita;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

/**
 * Registro de calificaciones (estrellas 1-5) sobre citas completadas.
 * Una cita solo puede calificarse una vez, sin importar el origen.
 */
class CalificacionService
{
    public function registrar(Cita $cita, array $data, string $origen): Calificacion
    {
        if ($cita->estado !== Cita::ESTADO_COMPLETADA) {
            throw ValidationException::withMessages([
                'cita' => 'Solo se pueden calificar citas completadas.',
            ]);
        }

        if ($cita->calificacion()->exists()) {
            throw ValidationException::withMessages([
                'cita' => 'Esta cita ya fue calificada.',
            ]);
        }

        try {
            $calificacion = Calificacion::create([
                'cita_id' => $cita->id,
                'empleado_id' => $cita->empleado_id,
                'servicio_id' => $cita->servicio_id,
                'estrellas' => $data['estrellas'],
                'comentario' => $data['comentario'] ?? null,
                'origen' => $origen,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'cita' => 'Esta cita ya fue calificada.',
            ]);
        }

        return $calificacion->load(['empleado', 'servicio']);
    }
}
