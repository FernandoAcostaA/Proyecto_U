<?php

namespace Tests\Feature;

use App\Models\TipoCorte;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PublicoTest extends TestCase
{
    use DatabaseTransactions;
    use InteractuaConAgenda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararAgenda();

        TipoCorte::create([
            'nombre' => 'Fade Publico',
            'descripcion' => 'Fade publico',
            'activo' => true,
        ]);
    }

    public function test_catalogo_publico(): void
    {
        $this->getJson('/api/v1/publico/catalogo')
            ->assertOk()
            ->assertJsonStructure([
                'negocio',
                'tipos_corte',
                'servicios',
                'barberos',
            ]);
    }

    public function test_barberos_publicos(): void
    {
        $this->getJson('/api/v1/publico/barberos')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $this->empleado->id,
            ]);
    }

    public function test_servicios_publicos(): void
    {
        $this->getJson('/api/v1/publico/servicios')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $this->servicio->id,
            ]);
    }

    public function test_tipos_corte_publicos(): void
    {
        $this->getJson('/api/v1/publico/tipos-corte')
            ->assertOk()
            ->assertJsonFragment(['nombre' => 'Fade Publico']);
    }
}
