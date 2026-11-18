<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recordatorio extends Model
{
    use HasFactory;

    public const TIPO_CONFIRMACION = 'confirmacion';

    public const TIPO_RECORDATORIO = 'recordatorio';

    public const TIPO_VERIFICACION = 'verificacion';

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_ENVIADO = 'enviado';

    public const ESTADO_ERROR = 'error';

    public const ESTADO_CANCELADO = 'cancelado';

    public const RESPUESTA_CONFIRMAR = 'confirmar';

    public const RESPUESTA_CANCELAR = 'cancelar';

    public const CANAL_EMAIL = 'email';

    public const CANAL_WHATSAPP = 'whatsapp';

    protected $table = 'recordatorios';

    protected $fillable = [
        'cita_id',
        'tipo',
        'canal',
        'destino',
        'proveedor',
        'costo_estimado',
        'asunto',
        'mensaje',
        'error',
        'id_externo',
        'respuesta_token',
        'fecha_envio_programada',
        'fecha_envio_real',
        'respondido_en',
        'respuesta',
        'estado',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'costo_estimado' => 'decimal:4',
            'fecha_envio_programada' => 'datetime',
            'fecha_envio_real' => 'datetime',
            'respondido_en' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class);
    }

    public static function tipos(): array
    {
        return [
            self::TIPO_CONFIRMACION,
            self::TIPO_RECORDATORIO,
            self::TIPO_VERIFICACION,
        ];
    }

    public static function canales(): array
    {
        return [
            self::CANAL_EMAIL,
            self::CANAL_WHATSAPP,
        ];
    }

    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_PENDIENTE)
            ->where('fecha_envio_programada', '<=', now());
    }

    public function scopeDelTipo(Builder $query, string $tipo): Builder
    {
        return $query->where('tipo', $tipo);
    }
}
