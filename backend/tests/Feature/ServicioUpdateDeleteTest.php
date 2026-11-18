<?php

namespace Tests\Feature;

use App\Models\Servicio;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ServicioUpdateDeleteTest extends TestCase
{
    use DatabaseTransactions;
    use InteractuaConAgenda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararAgenda();
        Sanctum::actingAs($this->admin);
    }

    public function test_actualizar_servicio(): void
    {
        $servicio = Servicio::create([
            'nombre' => 'Servicio Original',
            'duracion_min' => 30,
            'precio' => 25,
        ]);

        $this->patchJson('/api/v1/servicios/'.$servicio->id, [
            'nombre' => 'Servicio Editado',
        ])->assertOk()
            ->assertJsonPath('data.nombre', 'Servicio Editado');

        $this->assertDatabaseHas('servicios', [
            'id' => $servicio->id,
            'nombre' => 'Servicio Editado',
        ]);
    }

    public function test_eliminar_servicio(): void
    {
        $servicio = Servicio::create([
            'nombre' => 'Servicio Eliminable',
            'duracion_min' => 30,
            'precio' => 25,
        ]);

        $this->deleteJson('/api/v1/servicios/'.$servicio->id)->assertOk();

        $this->assertDatabaseMissing('servicios', [
            'id' => $servicio->id,
        ]);
    }
}
