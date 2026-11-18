<?php

namespace App\Services\Auth;

use App\Models\Cliente;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Logica de negocio de autenticacion y registro.
 * Controllers delgados: toda la logica vive aca.
 */
class AuthService
{
    /**
     * Registra un cliente final: crea su ficha en clientes y su acceso al sistema.
     */
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $cliente = Cliente::create([
                'nombre' => $data['nombre'],
                'telefono' => $data['telefono'],
                'email' => $data['email'] ?? null,
                'consentimiento_aceptado' => true,
                'consentimiento_en' => now(),
                'consentimiento_version' => Cliente::CONSENTIMIENTO_VERSION,
            ]);

            $usuario = Usuario::create([
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'rol_id' => Rol::where('nombre', Rol::CLIENTE)->firstOrFail()->id,
            ]);

            $cliente->update(['usuario_id' => $usuario->id]);

            return [
                'usuario' => $usuario->load('rol', 'cliente'),
                'cliente' => $cliente,
                'token' => $this->issueToken($usuario),
            ];
        });
    }

    public function login(array $data): array
    {
        $usuario = Usuario::where('email', $data['email'])->first();

        if (! $usuario || ! Hash::check($data['password'], $usuario->password)) {
            throw new AuthenticationException('Credenciales invalidas.');
        }

        return [
            'usuario' => $usuario->load('rol', 'cliente'),
            'token' => $this->issueToken($usuario),
        ];
    }

    public function logout(Usuario $usuario): void
    {
        $usuario->currentAccessToken()->delete();
    }

    /**
     * Genera un token Sanctum para el usuario autenticado.
     */
    protected function issueToken(Usuario $usuario): string
    {
        return $usuario->createToken('blackhound-token', [$usuario->rolNombre() ?? Rol::CLIENTE])->plainTextToken;
    }
}
