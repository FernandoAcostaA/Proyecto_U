<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RegistroTest extends TestCase
{
    use DatabaseTransactions;

    private string $telefono = '900001111';

    public function test_registro_exitoso_crea_cliente_y_permite_login(): void
    {
        $rol = Rol::firstOrCreate([
            'nombre' => Rol::CLIENTE,
        ], [
            'descripcion' => 'Cliente',
        ]);

        $datos = [
            'nombre' => 'Cliente Nuevo',
            'telefono' => $this->telefono,
            'email' => 'cliente-nuevo@test.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'consentimiento' => true,
        ];

        $this->postJson('/api/v1/auth/register', $datos)
            ->assertCreated()
            ->assertJsonPath('usuario.rol', Rol::CLIENTE)
            ->assertJsonPath('cliente.nombre', 'Cliente Nuevo')
            ->assertJsonPath('cliente.telefono', $this->telefono)
            ->assertJsonPath('cliente.email', 'cliente-nuevo@test.com')
            ->assertJsonPath('cliente.consentimiento_aceptado', true);

        $cliente = Cliente::where('telefono', $this->telefono)->firstOrFail();

        $usuario = Usuario::where('email', 'cliente-nuevo@test.com')->firstOrFail();

        $this->assertSame($rol->id, $usuario->rol_id);
        $this->assertSame($usuario->id, $cliente->usuario_id);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'cliente-nuevo@test.com',
            'password' => 'secret123',
        ])->assertOk()
            ->assertJsonPath('usuario.rol', Rol::CLIENTE)
            ->assertJsonPath('usuario.email', 'cliente-nuevo@test.com');
    }

    public function test_telefono_duplicado_devuelve_422(): void
    {
        Rol::firstOrCreate([
            'nombre' => Rol::CLIENTE,
        ], [
            'descripcion' => 'Cliente',
        ]);

        Cliente::create([
            'nombre' => 'Cliente Original',
            'telefono' => $this->telefono,
            'email' => 'cliente-original@test.com',
        ]);

        Usuario::create([
            'email' => 'cliente-original@test.com',
            'password' => bcrypt('secret123'),
            'rol_id' => Rol::where('nombre', Rol::CLIENTE)->firstOrFail()->id,
        ]);

        $this->postJson('/api/v1/auth/register', [
            'nombre' => 'Cliente Nuevo',
            'telefono' => $this->telefono,
            'email' => 'cliente-nuevo@test.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'consentimiento' => true,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('telefono');
    }
}
