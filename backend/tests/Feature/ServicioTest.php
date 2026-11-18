<?php

namespace Tests\Feature;

use App\Models\Empleado;
use App\Models\Rol;
use App\Models\Servicio;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ServicioTest extends TestCase
{
    use DatabaseTransactions;

    public function test_servicio_puede_crear_y_muestra_empleado_asignado(): void
    {
        $empleado = Empleado::create([
            'nombre' => 'Barbero Test',
            'telefono' => '900002111',
            'comision_base' => 40,
            'fecha_ingreso' => now()->subYear()->toDateString(),
        ]);

        $usuario = $this->crearAdmin();

        Sanctum::actingAs($usuario);

        $response = $this->postJson('/api/v1/servicios', [
            'nombre' => 'Corte Fade',
            'duracion_min' => 30,
            'precio' => 25.00,
            'porcentaje_comision' => 40,
            'empleados' => [$empleado->id],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.nombre', 'Corte Fade')
            ->assertJsonPath('data.duracion_min', 30)
            ->assertJsonPath('data.precio', '25.00');

        $servicioId = $response->json('data.id');

        $this->getJson('/api/v1/servicios/'.$servicioId)
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Corte Fade')
            ->assertJsonPath('data.empleados.0.id', $empleado->id);

        $this->assertTrue(
            Servicio::where('nombre', 'Corte Fade')
                ->whereHas('empleados', fn ($q) => $q->where('empleados.id', $empleado->id))
                ->exists()
        );
    }

    public function test_trabajador_puede_leer_servicios_y_no_puede_crear(): void
    {
        $usuario = $this->crearTrabajador();

        Sanctum::actingAs($usuario);

        $this->getJson('/api/v1/servicios')->assertOk();

        $this->postJson('/api/v1/servicios', [
            'nombre' => 'Corte Trabajador',
            'duracion_min' => 30,
            'precio' => 25,
        ])->assertStatus(403);
    }

    private function crearAdmin(): Usuario
    {
        $rol = Rol::firstOrCreate([
            'nombre' => Rol::ADMIN,
        ], [
            'descripcion' => 'Administrador',
        ]);

        return Usuario::create([
            'email' => 'admin-servicios@test.com',
            'password' => bcrypt('secret123'),
            'rol_id' => $rol->id,
        ]);
    }

    private function crearTrabajador(): Usuario
    {
        $rol = Rol::firstOrCreate([
            'nombre' => Rol::TRABAJADOR,
        ], [
            'descripcion' => 'Trabajador',
        ]);

        return Usuario::create([
            'email' => 'trabajador-servicios@test.com',
            'password' => bcrypt('secret123'),
            'rol_id' => $rol->id,
        ]);
    }
}
