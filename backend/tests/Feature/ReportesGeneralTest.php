<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportesGeneralTest extends TestCase
{
    use DatabaseTransactions;
    use InteractuaConAgenda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararAgenda();
        Sanctum::actingAs($this->admin);
    }

    public function test_ocupacion_devuelve_tipo(): void
    {
        $this->getJson('/api/v1/reportes/ocupacion')
            ->assertOk()
            ->assertJsonPath('tipo', 'ocupacion');
    }

    public function test_stock_devuelve_tipo(): void
    {
        $this->getJson('/api/v1/reportes/stock')
            ->assertOk()
            ->assertJsonPath('tipo', 'stock');
    }
}
