<?php

namespace Tests\Feature;

use App\Models\Rol;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RolTest extends TestCase
{
    use DatabaseTransactions;
    use InteractuaConAgenda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararAgenda();
        Sanctum::actingAs($this->admin);
    }

    public function test_listado_de_roles(): void
    {
        $this->getJson('/api/v1/roles')
            ->assertOk()
            ->assertJsonFragment(['nombre' => Rol::ADMIN]);
    }
}
