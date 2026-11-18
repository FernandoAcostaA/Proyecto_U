<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuarios';

    protected $fillable = ['email', 'password', 'rol_id'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public const ROL_ADMIN = Rol::ADMIN;

    public const ROL_TRABAJADOR = Rol::TRABAJADOR;

    public const ROL_CLIENTE = Rol::CLIENTE;

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function empleado(): HasOne
    {
        return $this->hasOne(Empleado::class);
    }

    public function cliente(): HasOne
    {
        return $this->hasOne(Cliente::class, 'usuario_id');
    }

    public function rolNombre(): ?string
    {
        return $this->rol?->nombre;
    }

    public function isAdmin(): bool
    {
        return $this->rolNombre() === self::ROL_ADMIN;
    }

    public function isTrabajador(): bool
    {
        return $this->rolNombre() === self::ROL_TRABAJADOR;
    }

    public function isCliente(): bool
    {
        return $this->rolNombre() === self::ROL_CLIENTE;
    }

    public function puedeGestionar(): bool
    {
        return $this->isAdmin();
    }
}
