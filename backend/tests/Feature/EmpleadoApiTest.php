<?php

namespace Tests\Feature;

use App\Models\Empleado;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmpleadoApiTest extends TestCase
{
    use DatabaseTransactions;
    use InteractuaConAgenda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararAgenda();
        Sanctum::actingAs($this->admin);
    }

    public function test_listado_de_empleados(): void
    {
        $this->getJson('/api/v1/empleados')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $this->empleado->id,
                'nombre' => $this->empleado->nombre,
            ]);
    }

    public function test_ver_detalle_de_empleado(): void
    {
        $this->getJson('/api/v1/empleados/'.$this->empleado->id)
            ->assertOk()
            ->assertJsonPath('data.id', $this->empleado->id)
            ->assertJsonPath('data.nombre', $this->empleado->nombre);
    }

    public function test_crear_y_actualizar_empleado(): void
    {
        $response = $this->postJson('/api/v1/empleados', [
            'nombre' => 'Barbero Nuevo',
            'telefono' => '900300100',
            'comision_base' => 35,
            'fecha_ingreso' => now()->subMonth()->toDateString(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.nombre', 'Barbero Nuevo');

        $empleadoId = $response->json('data.id');

        $this->patchJson('/api/v1/empleados/'.$empleadoId, [
            'nombre' => 'Barbero Editado',
        ])->assertOk()
            ->assertJsonPath('data.nombre', 'Barbero Editado');

        $this->assertDatabaseHas('empleados', [
            'id' => $empleadoId,
            'nombre' => 'Barbero Editado',
        ]);
    }

    public function test_eliminar_empleado(): void
    {
        $empleado = Empleado::create([
            'nombre' => 'Barbero Eliminable',
            'fecha_ingreso' => now()->subMonth()->toDateString(),
        ]);

        $this->deleteJson('/api/v1/empleados/'.$empleado->id)->assertOk();

        $this->assertDatabaseMissing('empleados', [
            'id' => $empleado->id,
        ]);
    }

    public function test_subir_y_eliminar_foto(): void
    {
        Storage::fake('public');

        $empleado = Empleado::create([
            'nombre' => 'Barbero Foto',
            'fecha_ingreso' => now()->subMonth()->toDateString(),
        ]);

        $this->postJson('/api/v1/empleados/'.$empleado->id.'/foto', [
            'foto' => UploadedFile::fake()->image('barbero.png', width: 256, height: 256),
        ])->assertOk()
            ->assertJsonPath('data.id', $empleado->id)
            ->assertJsonPath('data.foto', url('storage/'.$empleado->fresh()->foto));

        Storage::disk('public')->assertExists($empleado->fresh()->foto);

        $this->deleteJson('/api/v1/empleados/'.$empleado->id.'/foto')
            ->assertOk()
            ->assertJsonPath('data.foto', null);

        $this->assertNull($empleado->fresh()->foto);
    }
}
