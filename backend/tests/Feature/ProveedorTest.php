<?php

namespace Tests\Feature;

use App\Models\Proveedor;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProveedorTest extends TestCase
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

        Sanctum::actingAs(Usuario::create([
            'email' => 'admin-proveedores@test.com',
            'password' => bcrypt('secret123'),
            'rol_id' => $rol->id,
        ]));
    }

    public function test_crear_y_ver_proveedor(): void
    {
        $response = $this->postJson('/api/v1/proveedores', [
            'nombre' => 'Distribuidora Prueba',
            'telefono' => '60023456',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.nombre', 'Distribuidora Prueba')
            ->assertJsonPath('data.telefono', '60023456');

        $id = $response->json('data.id');

        $this->getJson('/api/v1/proveedores/'.$id)
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Distribuidora Prueba');
    }

    public function test_actualizar_proveedor(): void
    {
        $proveedor = Proveedor::create([
            'nombre' => 'Proveedor Original',
            'telefono' => '60011111',
        ]);

        $this->patchJson('/api/v1/proveedores/'.$proveedor->id, [
            'nombre' => 'Proveedor Editado',
        ])->assertOk()
            ->assertJsonPath('data.nombre', 'Proveedor Editado');

        $this->assertDatabaseHas('proveedores', [
            'id' => $proveedor->id,
            'nombre' => 'Proveedor Editado',
        ]);
    }

    public function test_eliminar_proveedor(): void
    {
        $proveedor = Proveedor::create([
            'nombre' => 'Proveedor Eliminable',
            'telefono' => '60033322',
        ]);

        $this->deleteJson('/api/v1/proveedores/'.$proveedor->id)->assertOk();

        $this->assertDatabaseMissing('proveedores', [
            'id' => $proveedor->id,
        ]);
    }
}
