<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Servicio;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ComisionCajaTest extends TestCase
{
    use DatabaseTransactions;
    use InteractuaConAgenda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararAgenda();
        Sanctum::actingAs($this->admin);
    }

    public function test_comision_guarda_el_split_barbero_y_barberia(): void
    {
        $cita = $this->completarCita($this->servicio);

        $this->assertDatabaseHas('comisiones', [
            'cita_id' => $cita->id,
            'monto' => 10,
            'porcentaje_barbero' => 40,
            'porcentaje_barberia' => 60,
            'monto_barberia' => 15,
        ]);
    }

    public function test_servicio_sin_porcentaje_usa_el_default_85_15(): void
    {
        $servicio = Servicio::create([
            'nombre' => 'Corte Default',
            'duracion_min' => 30,
            'precio' => 25,
        ]);

        $this->empleado->servicios()->attach($servicio->id);

        $cita = $this->completarCita($servicio);

        $this->assertDatabaseHas('comisiones', [
            'cita_id' => $cita->id,
            'porcentaje_barbero' => 85,
            'porcentaje_barberia' => 15,
            'monto' => 21.25,
            'monto_barberia' => 3.75,
        ]);
    }

    public function test_resumen_y_cierre_diario_de_caja(): void
    {
        $cita = $this->completarCita($this->servicio);
        $hoy = $cita->fecha_hora_inicio->toDateString();

        $this->getJson('/api/v1/caja/resumen?fecha='.$hoy)
            ->assertOk()
            ->assertJsonPath('cantidad_pagos', 1)
            ->assertJsonPath('cantidad_citas', 1)
            ->assertJsonPath('total_cobrado', 25)
            ->assertJsonPath('total_comisiones', 10)
            ->assertJsonPath('total_barberia', 15);

        $this->postJson('/api/v1/caja/cierres', [
            'fecha' => $hoy,
            'notas' => 'Cierre de prueba',
        ])
            ->assertCreated()
            ->assertJsonPath('data.fecha', $hoy)
            ->assertJsonPath('data.total_cobrado', '25.00')
            ->assertJsonPath('data.total_comisiones', '10.00')
            ->assertJsonPath('data.total_barberia', '15.00');

        $this->postJson('/api/v1/caja/cierres', ['fecha' => $hoy])
            ->assertStatus(422)
            ->assertJsonValidationErrors('fecha');
    }

    private function completarCita(Servicio $servicio): Cita
    {
        $inicio = now()->startOfDay()->addHours(10);

        $cita = Cita::create([
            'cliente_id' => $this->cliente->id,
            'empleado_id' => $this->empleado->id,
            'servicio_id' => $servicio->id,
            'fecha_hora_inicio' => $inicio,
            'fecha_hora_fin' => $inicio->copy()->addMinutes($servicio->duracion_min),
            'estado' => Cita::ESTADO_CONFIRMADA,
        ]);

        $this->postJson('/api/v1/citas/'.$cita->id.'/completar')->assertOk();

        return $cita->fresh();
    }
}
