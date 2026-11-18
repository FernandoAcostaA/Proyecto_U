<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Pago;
use App\Models\QrCobro;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QrCobroTest extends TestCase
{
    use DatabaseTransactions;
    use InteractuaConAgenda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararAgenda();
        Sanctum::actingAs($this->admin);
    }

    public function test_genera_qr_emvco_para_una_cita_completada(): void
    {
        $cita = $this->completarCita();

        $response = $this->postJson('/api/v1/citas/'.$cita->id.'/qr')
            ->assertCreated()
            ->assertJsonPath('data.estado', QrCobro::ESTADO_PENDIENTE)
            ->assertJsonPath('data.monto', '25.00');

        $referencia = $response->json('data.referencia');
        $payload = $response->json('data.payload');
        $svg = $response->json('svg');

        $this->assertStringStartsWith('BLK-', $referencia);
        $this->assertStringStartsWith('000201', $payload);
        $this->assertStringContainsString('540525.00', $payload);
        $this->assertStringContainsString('<svg', $svg);

        $this->assertDatabaseHas('qr_cobros', [
            'cita_id' => $cita->id,
            'referencia' => $referencia,
            'estado' => QrCobro::ESTADO_PENDIENTE,
        ]);
    }

    public function test_confirma_el_cobro_por_qr(): void
    {
        $cita = $this->completarCita();
        $referencia = $this->postJson('/api/v1/citas/'.$cita->id.'/qr')
            ->json('data.referencia');

        $this->postJson('/api/v1/publico/qr/'.$referencia.'/confirmar', [
            'monto' => 25,
            'id_externo' => 'wallet-test-001',
        ])
            ->assertOk()
            ->assertJsonPath('data.estado', QrCobro::ESTADO_CONFIRMADO);

        $this->assertDatabaseHas('qr_cobros', [
            'referencia' => $referencia,
            'estado' => QrCobro::ESTADO_CONFIRMADO,
        ]);

        $this->assertDatabaseHas('pagos', [
            'cita_id' => $cita->id,
            'estado' => Pago::ESTADO_PAGADO,
        ]);
    }

    public function test_no_confirma_un_qr_vencido(): void
    {
        $cita = $this->completarCita();
        $referencia = $this->postJson('/api/v1/citas/'.$cita->id.'/qr')
            ->json('data.referencia');

        QrCobro::where('referencia', $referencia)->update([
            'expira_en' => now()->subMinute(),
        ]);

        $this->postJson('/api/v1/publico/qr/'.$referencia.'/confirmar')
            ->assertStatus(422)
            ->assertJsonValidationErrors('referencia');
    }

    private function completarCita(): Cita
    {
        $inicio = now()->startOfDay()->addHours(10);

        $cita = Cita::create([
            'cliente_id' => $this->cliente->id,
            'empleado_id' => $this->empleado->id,
            'servicio_id' => $this->servicio->id,
            'fecha_hora_inicio' => $inicio,
            'fecha_hora_fin' => $inicio->copy()->addMinutes($this->servicio->duracion_min),
            'estado' => Cita::ESTADO_CONFIRMADA,
        ]);

        $this->postJson('/api/v1/citas/'.$cita->id.'/completar')->assertOk();

        return $cita->fresh();
    }
}
