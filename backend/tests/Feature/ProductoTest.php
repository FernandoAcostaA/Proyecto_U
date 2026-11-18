<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductoTest extends TestCase
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
            'email' => 'admin-productos@test.com',
            'password' => bcrypt('secret123'),
            'rol_id' => $rol->id,
        ]));
    }

    private function proveedor(): Proveedor
    {
        return Proveedor::create([
            'nombre' => 'Proveedor Productos',
            'telefono' => '60011111',
        ]);
    }

    public function test_crear_y_ver_producto(): void
    {
        $proveedor = $this->proveedor();

        $response = $this->postJson('/api/v1/productos', [
            'nombre' => 'Shampoo Profesional',
            'proveedor_id' => $proveedor->id,
            'stock_actual' => 10,
            'stock_minimo' => 2,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.nombre', 'Shampoo Profesional')
            ->assertJsonPath('data.stock_actual', 10)
            ->assertJsonPath('data.stock_minimo', 2)
            ->assertJsonPath('data.stock_bajo', false);

        $id = $response->json('data.id');

        $this->getJson('/api/v1/productos/'.$id)
            ->assertOk()
            ->assertJsonPath('data.proveedor', 'Proveedor Productos');
    }

    public function test_actualizar_producto(): void
    {
        $producto = Producto::create([
            'proveedor_id' => $this->proveedor()->id,
            'nombre' => 'Cera Moldeadora',
            'stock_actual' => 15,
            'stock_minimo' => 5,
        ]);

        $this->patchJson('/api/v1/productos/'.$producto->id, [
            'nombre' => 'Cera Moldeadora Editada',
            'stock_actual' => 12,
        ])->assertOk()
            ->assertJsonPath('data.nombre', 'Cera Moldeadora Editada')
            ->assertJsonPath('data.stock_actual', 12);

        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'nombre' => 'Cera Moldeadora Editada',
            'stock_actual' => 12,
        ]);
    }

    public function test_eliminar_producto(): void
    {
        $producto = Producto::create([
            'proveedor_id' => $this->proveedor()->id,
            'nombre' => 'Producto Eliminable',
            'stock_actual' => 5,
            'stock_minimo' => 2,
        ]);

        $this->deleteJson('/api/v1/productos/'.$producto->id)->assertOk();

        $this->assertDatabaseMissing('productos', [
            'id' => $producto->id,
        ]);
    }

    public function test_lista_de_stock_bajo(): void
    {
        Producto::create([
            'proveedor_id' => $this->proveedor()->id,
            'nombre' => 'Producto Bomber',
            'stock_actual' => 0,
            'stock_minimo' => 3,
        ]);

        $response = $this->getJson('/api/v1/productos/stock-bajo');

        $response->assertOk();

        $response->assertJsonFragment([
            'nombre' => 'Producto Bomber',
            'stock_actual' => 0,
            'stock_minimo' => 3,
            'stock_bajo' => true,
        ]);
    }

    public function test_producto_sin_proveedor_se_crea(): void
    {
        $this->postJson('/api/v1/productos', [
            'nombre' => 'Sin Proveedor',
            'stock_actual' => 7,
            'stock_minimo' => 2,
        ])->assertCreated()
            ->assertJsonPath('data.nombre', 'Sin Proveedor')
            ->assertJsonPath('data.stock_actual', 7);
    }
}
