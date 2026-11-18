<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrCobro extends Model
{
    use HasFactory;

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_CONFIRMADO = 'confirmado';

    public const ESTADO_EXPIRADO = 'expirado';

    public const ESTADO_ANULADO = 'anulado';

    protected $table = 'qr_cobros';

    protected $fillable = [
        'cita_id',
        'pago_id',
        'referencia',
        'payload',
        'monto',
        'estado',
        'expira_en',
        'confirmado_en',
        'confirmado_por',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'expira_en' => 'datetime',
            'confirmado_en' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class);
    }

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class);
    }

    public function confirmadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'confirmado_por');
    }

    public function estaVigente(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE
            && (! $this->expira_en || $this->expira_en->isFuture());
    }
}
