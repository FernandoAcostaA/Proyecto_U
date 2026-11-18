<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Servicio extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'duracion_min',
        'precio',
        'imagen',
        'porcentaje_comision',
    ];

    protected function casts(): array
    {
        return [
            'duracion_min' => 'integer',
            'precio' => 'decimal:2',
            'porcentaje_comision' => 'decimal:2',
        ];
    }

    public function empleados(): BelongsToMany
    {
        return $this->belongsToMany(Empleado::class, 'servicio_empleado');
    }

    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(Producto::class, 'servicio_producto')
            ->withPivot('cantidad_usada');
    }

    public function servicioProductos(): HasMany
    {
        return $this->hasMany(ServicioProducto::class);
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class);
    }

    public function calificaciones(): HasMany
    {
        return $this->hasMany(Calificacion::class);
    }
}
