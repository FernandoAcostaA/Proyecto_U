<?php

namespace Tests\Feature;

use App\Models\Calificacion;
use App\Models\Cita;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CalificacionTest extends TestCase
{
    use DatabaseTransactions;
    use InteractuaConAgenda;

    private Cita $cita;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararAgenda();
    }

    private function prepararCita(): Cita
    {
        $this->cita = Cita::create([
            'cliente_id' => $this->cliente->id,
            'empleado_id' => $this->empleado->id,
            'servicio_id' => $this->servicio->id,
            'fecha_hora_inicio' => now()->subHour(),
            'fecha_hora_fin' => now()->subHour()->addMinutes(30),
            'estado' => Cita::ESTADO_CONFIRMADA,
        ]);

        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/citas/'.$this->cita->id.'/completar')->assertOk();

        return $this->cita->fresh();
    }

    public function test_calificacion_panel_devuelve_201(): void
    {
        $cita = $this->prepararCita();

        $this->postJson('/api/v1/calificaciones', [
            'cita_id' => $cita->id,
            'estrellas' => 5,
            'comentario' => 'Excelente',
        ])->assertCreated()
            ->assertJsonPath('data.origen', Calificacion::ORIGEN_PANEL)
            ->assertJsonPath('data.estrellas', 5)
            ->assertJsonPath('data.cita_id', $cita->id);

        $this->assertDatabaseHas('calificaciones', [
            'cita_id' => $cita->id,
            'empleado_id' => $cita->empleado_id,
            'servicio_id' => $cita->servicio_id,
            'estrellas' => 5,
            'origen' => Calificacion::ORIGEN_PANEL,
        ]);
    }

    public function test_calificacion_panel_duplicada_devuelve_422(): void
    {
        $cita = $this->prepararCita();
        $token = $cita->calificacion_token;

        $this->postJson('/api/v1/calificaciones', [
            'cita_id' => $cita->id,
            'estrellas' => 4,
        ])->assertCreated();

        $this->postJson('/api/v1/calificaciones', [
            'cita_id' => $cita->id,
            'estrellas' => 4,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('cita');
    }

    public function test_calificacion_publica_devuelve_201(): void
    {
        $cita = $this->prepararCita();

        $this->postJson('/api/v1/calificaciones/publica/'.$cita->calificacion_token, [
            'estrellas' => 4,
        ])->assertCreated()
            ->assertJsonPath('data.origen', Calificacion::ORIGEN_CLIENTE)
            ->assertJsonPath('data.estrellas', 4)
            ->assertJsonPath('data.cita_id', $cita->id);
    }

    public function test_calificacion_publica_duplicada_devuelve_422(): void
    {
        $cita = $this->prepararCita();

        $this->postJson('/api/v1/calificaciones/publica/'.$cita->calificacion_token, [
            'estrellas' => 5,
        ])->assertCreated();

        $this->postJson('/api/v1/calificaciones/publica/'.$cita->calificacion_token, [
            'estrellas' => 4,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('cita');
    }

    public function test_calificacion_publica_token_invalido_devuelve_404(): void
    {
        $this->postJson('/api/v1/calificaciones/publica/'.Str::uuid()->toString(), [
            'estrellas' => 4,
        ])->assertStatus(404);
    }
}
