<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CierreCaja extends Model
{
    use HasFactory;

    protected $table = 'cierres_caja';

    protected $fillable = [
        'fecha',
        'cantidad_pagos',
        'cantidad_citas',
        'total_cobrado',
        'total_comisiones',
        'total_barberia',
        'total_pendiente_comisiones',
        'detalle',
        'notas',
        'cerrado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'total_cobrado' => 'decimal:2',
            'total_comisiones' => 'decimal:2',
            'total_barberia' => 'decimal:2',
            'total_pendiente_comisiones' => 'decimal:2',
            'detalle' => 'array',
        ];
    }

    public function cerradoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'cerrado_por');
    }
}
