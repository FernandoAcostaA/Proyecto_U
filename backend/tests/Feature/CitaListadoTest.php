<?php

namespace Tests\Feature;

use App\Models\Cita;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CitaListadoTest extends TestCase
{
    use DatabaseTransactions;
    use InteractuaConAgenda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararAgenda();
        Sanctum::actingAs($this->admin);
    }

    public function test_listado_y_detalle_de_citas(): void
    {
        $cita = Cita::create([
            'cliente_id' => $this->cliente->id,
            'empleado_id' => $this->empleado->id,
            'servicio_id' => $this->servicio->id,
            'fecha_hora_inicio' => now(),
            'fecha_hora_fin' => now()->addMinutes(30),
            'estado' => Cita::ESTADO_CONFIRMADA,
        ]);

        $this->getJson('/api/v1/citas')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $cita->id,
                'estado' => Cita::ESTADO_CONFIRMADA,
            ]);

        $this->getJson('/api/v1/citas/'.$cita->id)
            ->assertOk()
            ->assertJsonPath('data.id', $cita->id)
            ->assertJsonPath('data.estado', Cita::ESTADO_CONFIRMADA);
    }
}
