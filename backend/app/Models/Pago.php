<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    use HasFactory;

    public const METODO_EFECTIVO = 'efectivo';

    public const METODO_TARJETA = 'tarjeta';

    public const METODO_TRANSFERENCIA = 'transferencia';

    public const METODO_QR = 'qr';

    public const METODO_OTRO = 'otro';

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_PAGADO = 'pagado';

    public const ESTADO_REEMBOLSADO = 'reembolsado';

    protected $table = 'pagos';

    protected $fillable = [
        'cita_id',
        'monto',
        'metodo',
        'estado',
        'pagado_en',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'pagado_en' => 'datetime',
        ];
    }

    public static function metodos(): array
    {
        return [
            self::METODO_EFECTIVO,
            self::METODO_TARJETA,
            self::METODO_TRANSFERENCIA,
            self::METODO_QR,
            self::METODO_OTRO,
        ];
    }

    public static function estados(): array
    {
        return [
            self::ESTADO_PENDIENTE,
            self::ESTADO_PAGADO,
            self::ESTADO_REEMBOLSADO,
        ];
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class);
    }
}
