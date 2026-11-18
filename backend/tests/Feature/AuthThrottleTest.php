<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthThrottleTest extends TestCase
{
    use DatabaseTransactions;

    public function test_login_se_bloquea_tras_diez_intentos_fallidos(): void
    {
        $rol = Rol::firstOrCreate([
            'nombre' => Rol::ADMIN,
        ], [
            'descripcion' => 'Administrador',
        ]);

        Usuario::create([
            'email' => 'throttle-login@test.com',
            'password' => Hash::make('secret123'),
            'rol_id' => $rol->id,
        ]);

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'throttle-login@test.com',
                'password' => 'incorrecta',
            ])->assertStatus(401);
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => 'throttle-login@test.com',
            'password' => 'incorrecta',
        ])->assertStatus(429)
            ->assertJsonPath('message', 'Demasiados intentos de inicio de sesion. Intenta nuevamente en un minuto.');
    }

    public function test_registro_se_bloquea_tras_diez_intentos_invalidos(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/auth/register', [])->assertStatus(422);
        }

        $this->postJson('/api/v1/auth/register', [])
            ->assertStatus(429)
            ->assertJsonPath('message', 'Demasiados registros desde esta direccion. Intenta nuevamente en un minuto.');
    }
}
