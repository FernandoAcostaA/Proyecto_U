<?php

namespace Tests\Feature;

use App\Models\Cita;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReporteTest extends TestCase
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

    public function test_dashboard_refleja_cita_completada(): void
    {
        $this->completarCita();

        $this->getJson('/api/v1/reportes/dashboard')
            ->assertOk()
            ->assertJsonPath('tipo', 'dashboard')
            ->assertJsonPath('citas.completadas_periodo', 1)
            ->assertJsonPath('comisiones.pendiente', 10);
    }

    public function test_ventas_con_cita_completada(): void
    {
        $this->completarCita();

        $this->getJson('/api/v1/reportes/ventas')
            ->assertOk()
            ->assertJsonPath('tipo', 'ventas')
            ->assertJsonPath('resumen.citas_completadas', 1)
            ->assertJsonPath('resumen.pagos_cobrados', 1);
    }

    public function test_comisiones_con_cita_completada(): void
    {
        $this->completarCita();

        $this->getJson('/api/v1/reportes/comisiones')
            ->assertOk()
            ->assertJsonPath('tipo', 'comisiones')
            ->assertJsonPath('resumen.monto_total', 10);
    }
}
