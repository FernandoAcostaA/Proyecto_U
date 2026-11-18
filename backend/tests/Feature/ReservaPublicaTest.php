<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\HorarioDisponibilidad;
use App\Models\Rol;
use App\Models\Servicio;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReservaPublicaTest extends TestCase
{
    use DatabaseTransactions;

    private Empleado $empleado;

    private Servicio $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empleado = Empleado::create([
            'nombre' => 'Barbero Test',
            'telefono' => '900000001',
            'comision_base' => 40,
            'fecha_ingreso' => now()->subYear()->toDateString(),
        ]);

        $this->servicio = Servicio::create([
            'nombre' => 'Corte Test',
            'duracion_min' => 30,
            'precio' => 25,
            'porcentaje_comision' => 40,
        ]);

        $this->empleado->servicios()->attach($this->servicio->id);

        foreach (range(0, 6) as $dia) {
            HorarioDisponibilidad::create([
                'empleado_id' => $this->empleado->id,
                'dia_semana' => $dia,
                'hora_inicio' => '08:00:00',
                'hora_fin' => '20:00:00',
            ]);
        }
    }

    public function test_reserva_publica_se_confirma_con_el_token(): void
    {
        $response = $this->postJson('/api/v1/publico/citas', $this->payload(10));

        $response->assertCreated()
            ->assertJsonPath('data.estado', Cita::ESTADO_PENDIENTE_VERIFICACION);

        $token = $response->json('verificacion.token');
        $citaId = $response->json('data.id');

        $this->assertNotEmpty($token);

        $this->getJson('/api/v1/publico/citas/confirmar/'.$token)
            ->assertOk()
            ->assertJsonPath('data.estado', Cita::ESTADO_CONFIRMADA);

        $this->assertDatabaseHas('citas', [
            'id' => $citaId,
            'estado' => Cita::ESTADO_CONFIRMADA,
            'verificacion_token' => null,
        ]);

        $this->getJson('/api/v1/publico/citas/confirmar/'.$token)
            ->assertNotFound();
    }

    public function test_token_expirado_devuelve_410_y_cancela_la_reserva(): void
    {
        $response = $this->postJson('/api/v1/publico/citas', $this->payload(10));

        $token = $response->json('verificacion.token');
        $citaId = $response->json('data.id');

        Cita::whereKey($citaId)->update([
            'verificacion_expira_en' => now()->subMinute(),
        ]);

        $this->getJson('/api/v1/publico/citas/confirmar/'.$token)
            ->assertStatus(410);

        $this->assertDatabaseHas('citas', [
            'id' => $citaId,
            'estado' => Cita::ESTADO_CANCELADA,
            'verificacion_token' => null,
        ]);
    }

    public function test_telefono_no_puede_tener_mas_de_dos_reservas_activas(): void
    {
        $this->postJson('/api/v1/publico/citas', $this->payload(10))
            ->assertCreated();

        $this->postJson('/api/v1/publico/citas', $this->payload(11))
            ->assertCreated();

        $this->postJson('/api/v1/publico/citas', $this->payload(12))
            ->assertStatus(422)
            ->assertJsonValidationErrors('telefono');
    }

    public function test_comando_expira_reservas_pendientes(): void
    {
        $response = $this->postJson('/api/v1/publico/citas', $this->payload(10));

        $citaId = $response->json('data.id');

        Cita::whereKey($citaId)->update([
            'verificacion_expira_en' => now()->subMinute(),
        ]);

        $this->artisan('reservas:expirar')
            ->expectsOutput('Reservas expiradas: 1')
            ->assertSuccessful();

        $this->assertDatabaseHas('citas', [
            'id' => $citaId,
            'estado' => Cita::ESTADO_CANCELADA,
            'verificacion_token' => null,
        ]);
    }

    public function test_cliente_no_puede_cancelar_con_menos_de_dos_horas(): void
    {
        $rol = Rol::firstOrCreate([
            'nombre' => Rol::CLIENTE,
        ], [
            'descripcion' => 'Cliente',
        ]);

        $usuario = Usuario::create([
            'email' => 'cliente-cancelacion@test.com',
            'password' => Hash::make('secret123'),
            'rol_id' => $rol->id,
        ]);

        $cliente = Cliente::create([
            'usuario_id' => $usuario->id,
            'nombre' => 'Cliente Test',
            'telefono' => '900000002',
            'email' => 'cliente-cancelacion@test.com',
        ]);

        $inicio = now()->addHour();

        $cita = Cita::create([
            'cliente_id' => $cliente->id,
            'empleado_id' => $this->empleado->id,
            'servicio_id' => $this->servicio->id,
            'fecha_hora_inicio' => $inicio,
            'fecha_hora_fin' => $inicio->copy()->addMinutes(30),
            'estado' => Cita::ESTADO_CONFIRMADA,
        ]);

        Sanctum::actingAs($usuario);

        $this->postJson('/api/v1/mis-citas/'.$cita->id.'/cancelar')
            ->assertStatus(422)
            ->assertJsonValidationErrors('cita');

        $this->assertDatabaseHas('citas', [
            'id' => $cita->id,
            'estado' => Cita::ESTADO_CONFIRMADA,
        ]);
    }

    private function payload(int $hora): array
    {
        $inicio = now()->addDays(2)->setTime($hora, 0)->format('Y-m-d H:i:s');

        return [
            'nombre' => 'Cliente Publico',
            'telefono' => '900000003',
            'email' => 'cliente-publico@test.com',
            'consentimiento' => true,
            'empleado_id' => $this->empleado->id,
            'servicio_id' => $this->servicio->id,
            'fecha_hora_inicio' => $inicio,
        ];
    }
}
