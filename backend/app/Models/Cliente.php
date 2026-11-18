<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use HasFactory;

    public const CONSENTIMIENTO_VERSION = '1.0';

    protected $fillable = [
        'usuario_id',
        'nombre',
        'telefono',
        'email',
        'consentimiento_aceptado',
        'consentimiento_en',
        'consentimiento_version',
    ];

    protected function casts(): array
    {
        return [
            'consentimiento_aceptado' => 'boolean',
            'consentimiento_en' => 'datetime',
        ];
    }

    public function aceptarConsentimiento(?string $version = null): void
    {
        $this->forceFill([
            'consentimiento_aceptado' => true,
            'consentimiento_en' => now(),
            'consentimiento_version' => $version ?? self::CONSENTIMIENTO_VERSION,
        ])->save();
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class);
    }
}
