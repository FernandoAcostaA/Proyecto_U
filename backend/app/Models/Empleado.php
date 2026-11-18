<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empleado extends Model
{
    use HasFactory;

    protected $fillable = [
        'usuario_id',
        'nombre',
        'telefono',
        'foto',
        'comision_base',
        'fecha_ingreso',
    ];

    protected function casts(): array
    {
        return [
            'comision_base' => 'decimal:2',
            'fecha_ingreso' => 'date',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function servicios(): BelongsToMany
    {
        return $this->belongsToMany(Servicio::class, 'servicio_empleado');
    }

    public function horariosDisponibilidad(): HasMany
    {
        return $this->hasMany(HorarioDisponibilidad::class);
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class);
    }

    public function comisiones(): HasMany
    {
        return $this->hasMany(Comision::class);
    }

    public function calificaciones(): HasMany
    {
        return $this->hasMany(Calificacion::class);
    }

    public function fotoUrl(): ?string
    {
        return $this->foto ? url('storage/'.$this->foto) : null;
    }
}
