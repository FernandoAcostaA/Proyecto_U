<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\TipoCorte;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TipoCorteTest extends TestCase
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

        Sanctum::actingAs(Usuario::create([
            'email' => 'admin-tipos@test.com',
            'password' => bcrypt('secret123'),
            'rol_id' => $rol->id,
        ]));
    }

    private function crear(string $nombre = 'Fade Clasico', string $descripcion = 'Corte de degradado'): TipoCorte
    {
        return TipoCorte::create([
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'activo' => true,
        ]);
    }

    public function test_crear_y_ver_tipo_corte(): void
    {
        $response = $this->postJson('/api/v1/tipos-corte', [
            'nombre' => 'Fade Clasico',
            'descripcion' => 'Corte de degradado',
            'activo' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.nombre', 'Fade Clasico')
            ->assertJsonPath('data.activo', true);

        $id = $response->json('data.id');

        $this->getJson('/api/v1/tipos-corte/'.$id)
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Fade Clasico')
            ->assertJsonPath('data.descripcion', 'Corte de degradado');
    }

    public function test_actualizar_tipo_corte(): void
    {
        $tipo = $this->crear('Fade Clasico');

        $this->patchJson('/api/v1/tipos-corte/'.$tipo->id, [
            'nombre' => 'Fade Editado',
            'activo' => false,
        ])->assertOk()
            ->assertJsonPath('data.nombre', 'Fade Editado')
            ->assertJsonPath('data.activo', false);

        $this->assertDatabaseHas('tipos_corte', [
            'id' => $tipo->id,
            'nombre' => 'Fade Editado',
            'activo' => false,
        ]);
    }

    public function test_eliminar_tipo_corte(): void
    {
        $tipo = $this->crear('Fade Eliminable');

        $this->deleteJson('/api/v1/tipos-corte/'.$tipo->id)->assertOk();

        $this->assertDatabaseMissing('tipos_corte', [
            'id' => $tipo->id,
        ]);
    }

    public function test_nombre_duplicado_devuelve_422(): void
    {
        $this->crear('Fade Duplicado');

        $this->postJson('/api/v1/tipos-corte', [
            'nombre' => 'Fade Duplicado',
            'descripcion' => 'Corte repetido',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('nombre');
    }

    public function test_subir_imagen_guarda_url_y_archivo(): void
    {
        Storage::fake('public');

        $response = $this->post('/api/v1/tipos-corte', [
            'nombre' => 'Fade Con Imagen',
            'imagen' => UploadedFile::fake()->image('fade.png', width: 256, height: 256),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.nombre', 'Fade Con Imagen');

        $imagen = $response->json('data.imagen');

        $this->assertNotNull($imagen);

        $tipo = TipoCorte::where('nombre', 'Fade Con Imagen')->firstOrFail();

        Storage::disk('public')->assertExists($tipo->imagen);
    }

    public function test_eliminar_tipo_corte_borra_imagen(): void
    {
        Storage::fake('public');

        $response = $this->post('/api/v1/tipos-corte', [
            'nombre' => 'Fade Con Imagen',
            'imagen' => UploadedFile::fake()->image('fade.png', width: 256, height: 256),
        ]);

        $tipo = TipoCorte::where('nombre', 'Fade Con Imagen')->firstOrFail();

        $this->deleteJson('/api/v1/tipos-corte/'.$tipo->id)->assertOk();

        Storage::disk('public')->assertMissing($tipo->imagen);
    }
}
