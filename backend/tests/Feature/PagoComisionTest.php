<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Comision;
use App\Models\Pago;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PagoComisionTest extends TestCase
{
    use DatabaseTransactions;
    use InteractuaConAgenda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararAgenda();
    }

    private function prepararCita(): Cita
    {
        $cita = Cita::create([
            'cliente_id' => $this->cliente->id,
            'empleado_id' => $this->empleado->id,
            'servicio_id' => $this->servicio->id,
            'fecha_hora_inicio' => now()->subHour(),
            'fecha_hora_fin' => now()->subHour()->addMinutes(30),
            'estado' => Cita::ESTADO_CONFIRMADA,
        ]);

        Sanctum::actingAs($this->admin);

        return $cita->fresh();
    }

    public function test_pago_manual_para_cita_completada_devuelve_201(): void
    {
        $cita = $this->prepararCita();
        $cita->update(['estado' => Cita::ESTADO_COMPLETADA]);

        $this->postJson('/api/v1/pagos', [
            'cita_id' => $cita->id,
            'metodo' => Pago::METODO_TARJETA,
            'monto' => 20.50,
        ])->assertCreated()
            ->assertJsonPath('data.metodo', Pago::METODO_TARJETA)
            ->assertJsonPath('data.estado', Pago::ESTADO_PAGADO);

        $this->assertDatabaseHas('pagos', [
            'cita_id' => $cita->id,
            'metodo' => Pago::METODO_TARJETA,
            'monto' => 20.50,
            'estado' => Pago::ESTADO_PAGADO,
        ]);
    }

    public function test_pago_manual_para_cita_no_completada_devuelve_422(): void
    {
        $cita = $this->prepararCita();

        $this->postJson('/api/v1/pagos', [
            'cita_id' => $cita->id,
            'metodo' => Pago::METODO_EFECTIVO,
            'monto' => 15,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('cita_id');
    }

    public function test_pago_se_reembolsa_con_estado_reembolsado(): void
    {
        $cita = $this->prepararCita();

        $this->postJson('/api/v1/citas/'.$cita->id.'/completar')->assertOk();

        $pago = Pago::where('cita_id', $cita->id)->firstOrFail();

        $this->patchJson('/api/v1/pagos/'.$pago->id.'/estado', [
            'estado' => Pago::ESTADO_REEMBOLSADO,
        ])->assertOk()
            ->assertJsonPath('data.estado', Pago::ESTADO_REEMBOLSADO);

        $this->assertDatabaseHas('pagos', [
            'id' => $pago->id,
            'estado' => Pago::ESTADO_REEMBOLSADO,
        ]);
    }

    public function test_comision_se_genera_al_completar_cita(): void
    {
        $cita = $this->prepararCita();

        $this->postJson('/api/v1/citas/'.$cita->id.'/completar')->assertOk();

        $this->assertDatabaseHas('comisiones', [
            'cita_id' => $cita->id,
            'empleado_id' => $cita->empleado_id,
            'monto' => 10.00,
            'estado_pago' => Comision::ESTADO_PENDIENTE,
        ]);
    }
}
