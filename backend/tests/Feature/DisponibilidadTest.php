<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DisponibilidadTest extends TestCase
{
    use DatabaseTransactions;
    use InteractuaConAgenda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararAgenda();
    }

    public function test_empleado_disponible_paradoia(): void
    {
        Sanctum::actingAs($this->admin);

        $fecha = now()->addDay()->format('Y-m-d');
        $query = http_build_query([
            'servicio_id' => $this->servicio->id,
            'fecha' => $fecha,
        ]);

        $this->getJson('/api/v1/publico/disponibilidad?'.$query)
            ->assertOk()
            ->assertJsonPath('fecha', $fecha)
            ->assertJsonPath('servicio.id', $this->servicio->id)
            ->assertJsonPath('empleados_disponibles.0.empleado.id', $this->empleado->id)
            ->assertJsonPath('empleados_disponibles.0.horarios_libres.turnos.0', '08:00');
    }

    public function test_no_incluye_empleado_sin_horario(): void
    {
        Sanctum::actingAs($this->admin);

        $fecha = now()->addDay()->format('Y-m-d');
        $query = http_build_query([
            'servicio_id' => $this->servicio->id,
            'fecha' => $fecha,
        ]);

        $this->getJson('/api/v1/publico/disponibilidad?'.$query)
            ->assertOk();
    }
}
