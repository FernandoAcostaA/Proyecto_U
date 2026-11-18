<?php

namespace Tests\Feature;

use App\Models\Cita;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReprogramacionReporteTest extends TestCase
{
    use DatabaseTransactions;
    use InteractuaConAgenda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararAgenda();
        Sanctum::actingAs($this->admin);
    }

    public function test_reprograma_una_cita_respetando_disponibilidad(): void
    {
        $cita = $this->crearCita(now()->addDays(3)->setTime(10, 0));
        $nueva = now()->addDays(4)->setTime(11, 0);
        $nuevaFecha = $nueva->format('Y-m-d H:i:s');

        $this->patchJson('/api/v1/citas/'.$cita->id.'/reprogramar', [
            'fecha_hora_inicio' => $nuevaFecha,
        ])
            ->assertOk()
            ->assertJsonPath('data.fecha_hora_inicio', $nueva->toIso8601String());

        $this->assertDatabaseHas('citas', [
            'id' => $cita->id,
            'fecha_hora_inicio' => $nuevaFecha,
        ]);
    }

    public function test_no_reprograma_con_menos_de_dos_horas(): void
    {
        $inicio = now()->addHour();
        $cita = Cita::create([
            'cliente_id' => $this->cliente->id,
            'empleado_id' => $this->empleado->id,
            'servicio_id' => $this->servicio->id,
            'fecha_hora_inicio' => $inicio,
            'fecha_hora_fin' => $inicio->copy()->addMinutes(30),
            'estado' => Cita::ESTADO_CONFIRMADA,
        ]);

        $this->patchJson('/api/v1/citas/'.$cita->id.'/reprogramar', [
            'fecha_hora_inicio' => now()->addDays(3)->setTime(10, 0)->format('Y-m-d H:i:s'),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('cita');
    }

    public function test_reporte_de_citas_incluye_inasistencias_y_ocupacion(): void
    {
        $completada = $this->crearCita(now()->startOfDay()->addHours(10));
        $this->postJson('/api/v1/citas/'.$completada->id.'/completar')->assertOk();

        $noAsistio = $this->crearCita(now()->startOfDay()->addHours(12));
        $noAsistio->update(['estado' => Cita::ESTADO_NO_ASISTIO]);

        $this->getJson('/api/v1/reportes/citas')
            ->assertOk()
            ->assertJsonPath('tipo', 'citas')
            ->assertJsonPath('resumen.total_citas', 2)
            ->assertJsonPath('resumen.completadas', 1)
            ->assertJsonPath('resumen.no_asistio', 1)
            ->assertJsonPath('resumen.tasa_inasistencia', 50)
            ->assertJsonPath('por_empleado.0.empleado_id', $this->empleado->id);
    }

    private function crearCita($inicio): Cita
    {
        return Cita::create([
            'cliente_id' => $this->cliente->id,
            'empleado_id' => $this->empleado->id,
            'servicio_id' => $this->servicio->id,
            'fecha_hora_inicio' => $inicio,
            'fecha_hora_fin' => $inicio->copy()->addMinutes(30),
            'estado' => Cita::ESTADO_CONFIRMADA,
        ]);
    }
}
