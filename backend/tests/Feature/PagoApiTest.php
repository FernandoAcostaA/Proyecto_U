<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Pago;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PagoApiTest extends TestCase
{
    use DatabaseTransactions;
    use InteractuaConAgenda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararAgenda();
        Sanctum::actingAs($this->admin);
    }

    public function test_listado_y_detalle_de_pagos(): void
    {
        $cita = Cita::create([
            'cliente_id' => $this->cliente->id,
            'empleado_id' => $this->empleado->id,
            'servicio_id' => $this->servicio->id,
            'fecha_hora_inicio' => now()->subHour(),
            'fecha_hora_fin' => now()->subHour()->addMinutes(30),
            'estado' => Cita::ESTADO_COMPLETADA,
        ]);

        $pago = Pago::create([
            'cita_id' => $cita->id,
            'monto' => 25,
            'metodo' => Pago::METODO_TARJETA,
            'estado' => Pago::ESTADO_PAGADO,
            'pagado_en' => now(),
        ]);

        $this->getJson('/api/v1/pagos')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $pago->id,
                'cita' => [
                    'id' => $cita->id,
                    'cliente' => $this->cliente->nombre,
                    'servicio' => $this->servicio->nombre,
                ],
            ]);

        $this->getJson('/api/v1/pagos/'.$pago->id)
            ->assertOk()
            ->assertJsonPath('data.id', $pago->id)
            ->assertJsonPath('data.estado', Pago::ESTADO_PAGADO);
    }
}
