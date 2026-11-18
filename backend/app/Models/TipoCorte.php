<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoCorte extends Model
{
    use HasFactory;

    protected $table = 'tipos_corte';

    protected $fillable = [
        'nombre',
        'descripcion',
        'imagen',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function imagenUrl(): ?string
    {
        return $this->imagen ? url('storage/'.$this->imagen) : null;
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class);
    }
}
