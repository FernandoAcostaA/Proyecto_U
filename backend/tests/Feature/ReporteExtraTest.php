<?php

namespace Tests\Feature;

use App\Models\Cita;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReporteExtraTest extends TestCase
{
    use DatabaseTransactions;
    use InteractuaConAgenda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararAgenda();
        Sanctum::actingAs($this->admin);
    }

    private function completarCita(): Cita
    {
        $cita = Cita::create([
            'cliente_id' => $this->cliente->id,
            'empleado_id' => $this->empleado->id,
            'servicio_id' => $this->servicio->id,
            'fecha_hora_inicio' => now()->subHour(),
            'fecha_hora_fin' => now()->subHour()->addMinutes(30),
            'estado' => Cita::ESTADO_CONFIRMADA,
        ]);

        $this->postJson('/api/v1/citas/'.$cita->id.'/completar')->assertOk();

        return $cita->fresh();
    }

    public function test_servicios_mas_solicitados_ranking(): void
    {
        $cita = $this->completarCita();

        $this->getJson('/api/v1/reportes/servicios-mas-solicitados')
            ->assertOk()
            ->assertJsonPath('tipo', 'servicios-mas-solicitados')
            ->assertJsonPath('top.0.servicio_id', $this->servicio->id)
            ->assertJsonPath('top.0.cantidad', 1);
    }

    public function test_clientes_frecuentes_ranking(): void
    {
        $cita = $this->completarCita();

        $this->getJson('/api/v1/reportes/clientes-frecuentes')
            ->assertOk()
            ->assertJsonPath('tipo', 'clientes-frecuentes')
            ->assertJsonPath('top.0.cliente_id', $this->cliente->id)
            ->assertJsonPath('top.0.cantidad_visitas', 1);
    }
}
