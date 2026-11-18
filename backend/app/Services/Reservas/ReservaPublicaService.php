<?php

namespace App\Services\Reservas;

use App\Models\Cita;
use App\Models\Cliente;
use App\Services\Citas\CitaService;
use App\Services\Notificaciones\NotificacionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Reserva publica con doble opt-in simulado: la cita queda en
 * `pendiente_verificacion` hasta que el cliente abre el enlace enviado
 * (en este proyecto el envio se registra en el log y se devuelve para pruebas).
 */
class ReservaPublicaService
{
    public function __construct(
        protected CitaService $citas,
        protected NotificacionService $notificaciones,
    ) {}

    /**
     * @return array{cita:Cita, token:string, url:string, expira_en:Carbon}
     */
    public function crear(array $data): array
    {
        return DB::transaction(function () use ($data) {
            Cita::expirarVerificacionesPendientes();

            $cliente = Cliente::firstOrCreate(
                ['telefono' => $data['telefono']],
                ['nombre' => $data['nombre'], 'email' => $data['email'] ?? null],
            );

            $cliente->aceptarConsentimiento();

            $this->validarLimiteDeReservas($cliente);

            $cita = $this->citas->crear(
                [...$data, 'cliente_id' => $cliente->id],
                Cita::ESTADO_PENDIENTE_VERIFICACION,
                false,
            );

            $token = Str::random(64);
            $expiraEn = now()->addMinutes((int) config('blackhound.reserva_verificacion_minutos', 30));

            $cita->update([
                'verificacion_token' => $token,
                'verificacion_expira_en' => $expiraEn,
            ]);

            $url = route('publico.citas.confirmar', ['token' => $token]);

            $this->notificaciones->programarVerificacion($cita, $url);

            Log::info('Reserva publica pendiente de verificacion.', [
                'cita_id' => $cita->id,
                'telefono' => $cliente->telefono,
                'enlace' => $url,
                'expira_en' => $expiraEn->toIso8601String(),
            ]);

            return [
                'cita' => $cita->fresh(['cliente', 'empleado', 'servicio', 'tipoCorte']),
                'token' => $token,
                'url' => $url,
                'expira_en' => $expiraEn,
            ];
        });
    }

    public function confirmar(string $token): Cita
    {
        $cita = Cita::where('verificacion_token', $token)->first();

        if (! $cita) {
            abort(404, 'El enlace de verificacion no es valido.');
        }

        if ($cita->estado === Cita::ESTADO_CONFIRMADA) {
            throw ValidationException::withMessages([
                'token' => 'La cita ya fue confirmada.',
            ]);
        }

        if ($cita->estado !== Cita::ESTADO_PENDIENTE_VERIFICACION) {
            throw ValidationException::withMessages([
                'token' => 'La reserva ya no esta pendiente de verificacion.',
            ]);
        }

        if (! $cita->verificacion_expira_en || $cita->verificacion_expira_en->isPast()) {
            $cita->update([
                'estado' => Cita::ESTADO_CANCELADA,
                'verificacion_token' => null,
                'verificacion_expira_en' => null,
            ]);

            abort(410, 'El enlace de verificacion expiro.');
        }

        DB::transaction(function () use ($cita) {
            $this->citas->cambiarEstado($cita, Cita::ESTADO_CONFIRMADA);

            $cita->update([
                'verificacion_token' => null,
                'verificacion_expira_en' => null,
            ]);
        });

        return $cita->fresh(['cliente', 'empleado', 'servicio', 'tipoCorte']);
    }

    public function expirarPendientes(): int
    {
        return Cita::expirarVerificacionesPendientes();
    }

    protected function validarLimiteDeReservas(Cliente $cliente): void
    {
        $maximas = (int) config('blackhound.reserva_max_activas_por_cliente', 2);

        $activas = $cliente->citas()
            ->whereIn('estado', [
                Cita::ESTADO_PENDIENTE_VERIFICACION,
                Cita::ESTADO_PENDIENTE,
                Cita::ESTADO_CONFIRMADA,
            ])
            ->where('fecha_hora_inicio', '>=', now())
            ->count();

        if ($activas >= $maximas) {
            throw ValidationException::withMessages([
                'telefono' => 'El telefono ya tiene el maximo de reservas activas permitidas.',
            ]);
        }
    }
}
