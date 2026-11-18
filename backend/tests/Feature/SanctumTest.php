<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SanctumTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $rol = Rol::firstOrCreate([
            'nombre' => Rol::ADMIN,
        ], [
            'descripcion' => 'Administrador',
        ]);

        Usuario::create([
            'email' => 'sanctum-admin@test.com',
            'password' => bcrypt('secret123'),
            'rol_id' => $rol->id,
        ]);
    }

    private function login(): string
    {
        return $this->postJson('/api/v1/auth/login', [
            'email' => 'sanctum-admin@test.com',
            'password' => 'secret123',
        ])->assertOk()->json('token');
    }

    public function test_token_permite_acceso_a_me(): void
    {
        $token = $this->login();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('usuario.email', 'sanctum-admin@test.com')
            ->assertJsonPath('usuario.rol', Rol::ADMIN);
    }

    public function test_logout_revoca_token(): void
    {
        $usuario = Usuario::where('email', 'sanctum-admin@test.com')->firstOrFail();

        $token = $this->login();

        $this->postJson('/api/v1/auth/logout', [], ['Authorization' => 'Bearer '.$token])
            ->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $usuario->id,
        ]);
    }

    public function test_token_invalido_no_autentica(): void
    {
        $this->withHeader('Authorization', 'Bearer token-invalido')
            ->getJson('/api/v1/me')
            ->assertStatus(401);
    }
}
