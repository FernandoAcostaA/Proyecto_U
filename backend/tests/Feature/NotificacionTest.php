<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Recordatorio;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificacionTest extends TestCase
{
    use DatabaseTransactions;
    use InteractuaConAgenda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararAgenda();
        Sanctum::actingAs($this->admin);
    }

    public function test_crear_cita_genera_confirmacion_y_recordatorio_por_email(): void
    {
        $response = $this->postJson('/api/v1/citas', $this->payload())
            ->assertCreated();

        $citaId = $response->json('data.id');

        $this->artisan('notificaciones:procesar')->assertSuccessful();

        $confirmacion = Recordatorio::where('cita_id', $citaId)
            ->where('tipo', Recordatorio::TIPO_CONFIRMACION)
            ->firstOrFail();

        $this->assertSame(Recordatorio::CANAL_EMAIL, $confirmacion->canal);
        $this->assertSame('cliente-agenda@test.com', $confirmacion->destino);
        $this->assertSame(Recordatorio::ESTADO_ENVIADO, $confirmacion->estado);
        $this->assertSame('array', $confirmacion->proveedor);

        $recordatorio = Recordatorio::where('cita_id', $citaId)
            ->where('tipo', Recordatorio::TIPO_RECORDATORIO)
            ->firstOrFail();

        $this->assertNotNull($recordatorio->respuesta_token);
        $this->assertSame(Recordatorio::ESTADO_ENVIADO, $recordatorio->estado);
        $this->assertStringContainsString('/confirmar', $recordatorio->mensaje);
        $this->assertStringContainsString('/cancelar', $recordatorio->mensaje);
    }

    public function test_cliente_sin_email_recibe_notificacion_por_whatsapp(): void
    {
        $cliente = Cliente::create([
            'nombre' => 'Cliente Sin Email',
            'telefono' => '900009999',
        ]);

        $response = $this->postJson('/api/v1/citas', $this->payload([
            'cliente_id' => $cliente->id,
        ]))->assertCreated();

        $this->artisan('notificaciones:procesar')->assertSuccessful();

        $notificacion = Recordatorio::where('cita_id', $response->json('data.id'))
            ->where('tipo', Recordatorio::TIPO_CONFIRMACION)
            ->firstOrFail();

        $this->assertSame(Recordatorio::CANAL_WHATSAPP, $notificacion->canal);
        $this->assertSame('900009999', $notificacion->destino);
        $this->assertSame('simulado', $notificacion->proveedor);
        $this->assertSame(Recordatorio::ESTADO_ENVIADO, $notificacion->estado);
    }

    public function test_cliente_confirma_desde_el_recordatorio(): void
    {
        $citaId = $this->postJson('/api/v1/citas', $this->payload())->json('data.id');

        $recordatorio = Recordatorio::where('cita_id', $citaId)
            ->where('tipo', Recordatorio::TIPO_RECORDATORIO)
            ->firstOrFail();

        $this->getJson('/api/v1/publico/citas/notificacion/'.$recordatorio->respuesta_token.'/confirmar')
            ->assertOk()
            ->assertJsonPath('data.estado', Cita::ESTADO_CONFIRMADA);

        $this->assertDatabaseHas('citas', [
            'id' => $citaId,
            'estado' => Cita::ESTADO_CONFIRMADA,
        ]);

        $this->assertDatabaseHas('recordatorios', [
            'id' => $recordatorio->id,
            'respuesta' => Recordatorio::RESPUESTA_CONFIRMAR,
        ]);

        $this->getJson('/api/v1/publico/citas/notificacion/'.$recordatorio->respuesta_token.'/confirmar')
            ->assertStatus(422);
    }

    public function test_cliente_cancela_desde_el_recordatorio(): void
    {
        $citaId = $this->postJson('/api/v1/citas', $this->payload())->json('data.id');

        $recordatorio = Recordatorio::where('cita_id', $citaId)
            ->where('tipo', Recordatorio::TIPO_RECORDATORIO)
            ->firstOrFail();

        $this->getJson('/api/v1/publico/citas/notificacion/'.$recordatorio->respuesta_token.'/cancelar')
            ->assertOk()
            ->assertJsonPath('data.estado', Cita::ESTADO_CANCELADA);

        $this->assertDatabaseHas('citas', [
            'id' => $citaId,
            'estado' => Cita::ESTADO_CANCELADA,
        ]);

        $this->assertDatabaseHas('recordatorios', [
            'id' => $recordatorio->id,
            'respuesta' => Recordatorio::RESPUESTA_CANCELAR,
        ]);
    }

    public function test_historial_de_notificaciones_expone_estado_y_costo(): void
    {
        $this->postJson('/api/v1/citas', $this->payload())->assertCreated();
        $this->artisan('notificaciones:procesar')->assertSuccessful();

        $this->getJson('/api/v1/notificaciones')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'cita_id',
                        'tipo',
                        'canal',
                        'destino',
                        'estado',
                        'proveedor',
                        'costo_estimado',
                        'fecha_envio_programada',
                        'fecha_envio_real',
                    ],
                ],
            ])
            ->assertJsonFragment([
                'tipo' => Recordatorio::TIPO_CONFIRMACION,
                'estado' => Recordatorio::ESTADO_ENVIADO,
            ]);
    }

    private function payload(array $overrides = []): array
    {
        return [
            'cliente_id' => $this->cliente->id,
            'empleado_id' => $this->empleado->id,
            'servicio_id' => $this->servicio->id,
            'fecha_hora_inicio' => now()->addDays(3)->setTime(10, 0)->format('Y-m-d H:i:s'),
            ...$overrides,
        ];
    }
}
