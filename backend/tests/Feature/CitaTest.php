<?php

namespace Tests\Feature;

use App\Models\Empleado;
use App\Models\HorarioDisponibilidad;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CitaTest extends TestCase
{
    use DatabaseTransactions;
    use InteractuaConAgenda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararAgenda();
    }

    public function test_cita_se_crea_validada_por_disponibilidad(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/citas', [
            'cliente_id' => $this->cliente->id,
            'empleado_id' => $this->empleado->id,
            'servicio_id' => $this->servicio->id,
            'fecha_hora_inicio' => $this->inicio(10),
        ])->assertCreated()
            ->assertJsonPath('data.cliente.id', $this->cliente->id)
            ->assertJsonPath('data.empleado.id', $this->empleado->id)
            ->assertJsonPath('data.servicio.id', $this->servicio->id)
            ->assertJsonPath('data.estado', 'pendiente');
    }

    public function test_cita_solapada_devuelve_422(): void
    {
        Sanctum::actingAs($this->admin);

        $datos = [
            'cliente_id' => $this->cliente->id,
            'empleado_id' => $this->empleado->id,
            'servicio_id' => $this->servicio->id,
            'fecha_hora_inicio' => $this->inicio(10),
        ];

        $this->postJson('/api/v1/citas', $datos)->assertCreated();

        $this->postJson('/api/v1/citas', $datos)
            ->assertStatus(422)
            ->assertJsonValidationErrors('fecha_hora_inicio');
    }

    public function test_cita_fuera_de_horario_devuelve_422(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/citas', [
            'cliente_id' => $this->cliente->id,
            'empleado_id' => $this->empleado->id,
            'servicio_id' => $this->servicio->id,
            'fecha_hora_inicio' => $this->inicio(7),
        ])->assertStatus(422)
            ->assertJsonValidationErrors('fecha_hora_inicio');
    }

    public function test_cita_con_empleado_no_capacitado_devuelve_422(): void
    {
        Sanctum::actingAs($this->admin);

        $empleado = Empleado::create([
            'nombre' => 'Barbero No Capacitado',
            'telefono' => '900002801',
            'comision_base' => 30,
            'fecha_ingreso' => now()->subYear()->toDateString(),
        ]);

        foreach (range(0, 6) as $dia) {
            HorarioDisponibilidad::create([
                'empleado_id' => $empleado->id,
                'dia_semana' => $dia,
                'hora_inicio' => '08:00:00',
                'hora_fin' => '20:00:00',
            ]);
        }

        $this->postJson('/api/v1/citas', [
            'cliente_id' => $this->cliente->id,
            'empleado_id' => $empleado->id,
            'servicio_id' => $this->servicio->id,
            'fecha_hora_inicio' => $this->inicio(11),
        ])->assertStatus(422)
            ->assertJsonValidationErrors('empleado_id');
    }

    private function inicio(int $hora): string
    {
        return now()->addDay()
            ->setTime($hora, 0, 0)
            ->format('Y-m-d H:i:s');
    }
}
