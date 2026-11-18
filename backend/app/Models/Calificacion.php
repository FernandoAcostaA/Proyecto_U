<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Calificacion extends Model
{
    use HasFactory;

    protected $table = 'calificaciones';

    public const ORIGEN_PANEL = 'panel';

    public const ORIGEN_CLIENTE = 'cliente';

    protected $fillable = [
        'cita_id',
        'empleado_id',
        'servicio_id',
        'estrellas',
        'comentario',
        'origen',
    ];

    protected function casts(): array
    {
        return [
            'estrellas' => 'integer',
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

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }
}
