<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClienteTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $rol = Rol::firstOrCreate([
            'nombre' => Rol::ADMIN,
        ], [
            'descripcion' => 'Administrador',
        ]);

        $usuario = Usuario::create([
            'email' => 'admin-clientes@test.com',
            'password' => bcrypt('secret123'),
            'rol_id' => $rol->id,
        ]);

        Sanctum::actingAs($usuario);
    }

    public function test_crear_y_ver_cliente(): void
    {
        $response = $this->postJson('/api/v1/clientes', [
            'nombre' => 'Cliente Test',
            'telefono' => '900003111',
            'email' => 'cliente-test@test.com',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.nombre', 'Cliente Test');

        $clienteId = $response->json('data.id');

        $this->getJson('/api/v1/clientes/'.$clienteId)
            ->assertOk()
            ->assertJsonPath('data.telefono', '900003111')
            ->assertJsonPath('data.email', 'cliente-test@test.com');
    }

    public function test_actualizar_cliente(): void
    {
        $response = $this->postJson('/api/v1/clientes', [
            'nombre' => 'Cliente Original',
            'telefono' => '900003222',
            'email' => 'cliente-original@test.com',
        ]);

        $clienteId = $response->json('data.id');

        $this->patchJson('/api/v1/clientes/'.$clienteId, [
            'nombre' => 'Cliente Editado',
        ])->assertOk()
            ->assertJsonPath('data.nombre', 'Cliente Editado');

        $this->assertDatabaseHas('clientes', [
            'id' => $clienteId,
            'nombre' => 'Cliente Editado',
        ]);
    }

    public function test_eliminar_cliente(): void
    {
        $response = $this->postJson('/api/v1/clientes', [
            'nombre' => 'Cliente Eliminable',
            'telefono' => '900003333',
            'email' => 'cliente-eliminable@test.com',
        ]);

        $clienteId = $response->json('data.id');

        $this->deleteJson('/api/v1/clientes/'.$clienteId)->assertOk();

        $this->assertDatabaseMissing('clientes', [
            'id' => $clienteId,
        ]);
    }

    public function test_crear_cliente_con_telefono_duplicado_devuelve_422(): void
    {
        $this->postJson('/api/v1/clientes', [
            'nombre' => 'Cliente Uno',
            'telefono' => '900003444',
            'email' => 'cliente-uno@test.com',
        ])->assertCreated();

        $this->postJson('/api/v1/clientes', [
            'nombre' => 'Cliente Duplicado',
            'telefono' => '900003444',
            'email' => 'cliente-duplicado@test.com',
        ])->assertStatus(422);
    }
}
