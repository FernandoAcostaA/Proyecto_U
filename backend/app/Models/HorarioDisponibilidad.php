<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HorarioDisponibilidad extends Model
{
    public const DOMINGO = 0;

    public const LUNES = 1;

    public const MARTES = 2;

    public const MIERCOLES = 3;

    public const JUEVES = 4;

    public const VIERNES = 5;

    public const SABADO = 6;

    protected $table = 'horarios_disponibilidad';

    protected $fillable = ['empleado_id', 'dia_semana', 'hora_inicio', 'hora_fin'];

    protected function casts(): array
    {
        return [
            'dia_semana' => 'integer',
            'hora_inicio' => 'string',
            'hora_fin' => 'string',
        ];
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public static function diasSemana(): array
    {
        return [
            self::DOMINGO => 'Domingo',
            self::LUNES => 'Lunes',
            self::MARTES => 'Martes',
            self::MIERCOLES => 'Miercoles',
            self::JUEVES => 'Jueves',
            self::VIERNES => 'Viernes',
            self::SABADO => 'Sabado',
        ];
    }

    public function getDiaSemanaNombreAttribute(): string
    {
        return self::diasSemana()[$this->dia_semana] ?? (string) $this->dia_semana;
    }
}
