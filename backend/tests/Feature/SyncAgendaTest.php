<?php

namespace Tests\Feature;

use App\Models\Empleado;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Servicio;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SyncAgendaTest extends TestCase
{
    use DatabaseTransactions;
    use InteractuaConAgenda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararAgenda();

        Sanctum::actingAs($this->admin);
    }

    public function test_empleado_puede_reemplazar_servicios(): void
    {
        $nuevoServicio = Servicio::create([
            'nombre' => 'Corte Reemplazo',
            'duracion_min' => 40,
            'precio' => 35,
            'porcentaje_comision' => 30,
        ]);

        $this->postJson('/api/v1/empleados/'.$this->empleado->id.'/servicios', [
            'servicios' => [$nuevoServicio->id],
        ])->assertOk()
            ->assertJsonCount(1, 'data.servicios')
            ->assertJsonPath('data.servicios.0.id', $nuevoServicio->id);
    }

    public function test_horarios_se_reemplazan(): void
    {
        $this->putJson('/api/v1/empleados/'.$this->empleado->id.'/horarios', [
            'horarios' => [
                [
                    'dia_semana' => 1,
                    'hora_inicio' => '09:00',
                    'hora_fin' => '17:00',
                ],
            ],
        ])->assertOk()
            ->assertJsonCount(1, 'data.horarios')
            ->assertJsonPath('data.horarios.0.dia_semana', 1)
            ->assertJsonPath('data.horarios.0.hora_inicio', '09:00:00')
            ->assertJsonPath('data.horarios.0.hora_fin', '17:00:00');
    }

    public function test_servicio_sincroniza_empleados(): void
    {
        $empleado = Empleado::create([
            'nombre' => 'Barbero Reemplazo',
            'telefono' => '901111111',
            'comision_base' => 35,
            'fecha_ingreso' => now()->subYear()->toDateString(),
        ]);

        $this->postJson('/api/v1/servicios/'.$this->servicio->id.'/empleados', [
            'empleados' => [$empleado->id],
        ])->assertOk()
            ->assertJsonCount(1, 'data.empleados')
            ->assertJsonPath('data.empleados.0.id', $empleado->id);
    }

    public function test_servicio_sincroniza_productos(): void
    {
        $proveedor = Proveedor::create([
            'nombre' => 'Proveedor Agenda',
            'telefono' => '900333222',
        ]);

        $producto = Producto::create([
            'proveedor_id' => $proveedor->id,
            'nombre' => 'Cera Agenda',
            'stock_actual' => 12,
            'stock_minimo' => 3,
        ]);

        $this->postJson('/api/v1/servicios/'.$this->servicio->id.'/productos', [
            'productos' => [
                [
                    'producto_id' => $producto->id,
                    'cantidad_usada' => 2,
                ],
            ],
        ])->assertOk()
            ->assertJsonCount(1, 'data.productos')
            ->assertJsonPath('data.productos.0.id', $producto->id);

        $this->assertDatabaseHas('servicio_producto', [
            'servicio_id' => $this->servicio->id,
            'producto_id' => $producto->id,
            'cantidad_usada' => 2,
        ]);
    }
}
