<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use DatabaseTransactions;

    private string $email = 'cliente-login@test.com';

    protected function setUp(): void
    {
        parent::setUp();

        Rol::firstOrCreate([
            'nombre' => Rol::CLIENTE,
        ], [
            'descripcion' => 'Cliente',
        ]);

        Usuario::create([
            'email' => $this->email,
            'password' => Hash::make('secret123'),
            'rol_id' => Rol::where('nombre', Rol::CLIENTE)->firstOrFail()->id,
        ]);
    }

    public function test_login_exitoso_devuelve_token(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email' => $this->email,
            'password' => 'secret123',
        ])->assertOk()
            ->assertJsonPath('usuario.rol', Rol::CLIENTE)
            ->assertJsonPath('usuario.email', $this->email);
    }

    public function test_login_con_credenciales_invalidas_devuelve_401(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email' => $this->email,
            'password' => 'incorrecta',
        ])->assertStatus(401);
    }
}
