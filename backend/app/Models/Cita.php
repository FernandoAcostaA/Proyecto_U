<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cita extends Model
{
    use HasFactory;

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_PENDIENTE_VERIFICACION = 'pendiente_verificacion';

    public const ESTADO_CONFIRMADA = 'confirmada';

    public const ESTADO_COMPLETADA = 'completada';

    public const ESTADO_CANCELADA = 'cancelada';

    public const ESTADO_NO_ASISTIO = 'no_asistio';

    protected $fillable = [
        'cliente_id',
        'empleado_id',
        'servicio_id',
        'tipo_corte_id',
        'fecha_hora_inicio',
        'fecha_hora_fin',
        'estado',
        'verificacion_token',
        'verificacion_expira_en',
        'calificacion_token',
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora_inicio' => 'datetime',
            'fecha_hora_fin' => 'datetime',
            'verificacion_expira_en' => 'datetime',
        ];
    }

    public static function estados(): array
    {
        return [
            self::ESTADO_PENDIENTE,
            self::ESTADO_PENDIENTE_VERIFICACION,
            self::ESTADO_CONFIRMADA,
            self::ESTADO_COMPLETADA,
            self::ESTADO_CANCELADA,
            self::ESTADO_NO_ASISTIO,
        ];
    }

    /**
     * Libera los cupos de reservas publicas cuyo enlace de verificacion expiro.
     */
    public static function expirarVerificacionesPendientes(): int
    {
        return static::where('estado', self::ESTADO_PENDIENTE_VERIFICACION)
            ->where('verificacion_expira_en', '<=', now())
            ->update([
                'estado' => self::ESTADO_CANCELADA,
                'verificacion_token' => null,
                'verificacion_expira_en' => null,
            ]);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }

    public function tipoCorte(): BelongsTo
    {
        return $this->belongsTo(TipoCorte::class);
    }

    public function calificacion(): HasOne
    {
        return $this->hasOne(Calificacion::class);
    }

    public function comision(): HasOne
    {
        return $this->hasOne(Comision::class);
    }

    public function pago(): HasOne
    {
        return $this->hasOne(Pago::class);
    }

    public function recordatorios(): HasMany
    {
        return $this->hasMany(Recordatorio::class);
    }

    public function movimientosStock(): HasMany
    {
        return $this->hasMany(MovimientoStock::class);
    }

    public function requireReminder(): bool
    {
        return in_array($this->estado, [
            self::ESTADO_PENDIENTE,
            self::ESTADO_CONFIRMADA,
        ], true);
    }
}
