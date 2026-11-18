<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comision extends Model
{
    use HasFactory;

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_PAGADO = 'pagado';

    protected $table = 'comisiones';

    protected $fillable = [
        'cita_id',
        'empleado_id',
        'monto',
        'porcentaje_barbero',
        'porcentaje_barberia',
        'monto_barberia',
        'estado_pago',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'porcentaje_barbero' => 'decimal:2',
            'porcentaje_barberia' => 'decimal:2',
            'monto_barberia' => 'decimal:2',
        ];
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class);
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }
}
