<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 30)->unique();
            $table->string('descripcion', 150)->nullable();
            $table->timestamps();
        });

        $now = now();

        DB::table('roles')->insert([
            ['nombre' => 'dueno', 'descripcion' => 'Dueno de la barberia, acceso total.', 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'admin', 'descripcion' => 'Administrador, gestion operativa y reportes.', 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'empleado', 'descripcion' => 'Trabajador/barbero, agenda y atencion de citas.', 'created_at' => $now, 'updated_at' => $now],
        ]);

        Schema::table('usuarios', function (Blueprint $table) {
            $table->foreignId('rol_id')->nullable()->after('password')->constrained('roles')->nullOnDelete();
        });

        DB::statement('UPDATE usuarios SET rol_id = (SELECT id FROM roles WHERE roles.nombre = usuarios.rol)');
        DB::statement("UPDATE usuarios SET rol_id = (SELECT id FROM roles WHERE nombre = 'empleado') WHERE rol_id IS NULL");
        DB::statement('ALTER TABLE usuarios ALTER COLUMN rol_id SET NOT NULL');

        DB::statement('ALTER TABLE usuarios DROP CONSTRAINT IF EXISTS usuarios_rol_check');

        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('rol');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('rol', 20)->default('empleado')->after('password');
        });

        DB::statement('UPDATE usuarios SET rol = (SELECT nombre FROM roles WHERE roles.id = usuarios.rol_id)');
        DB::statement("ALTER TABLE usuarios ADD CONSTRAINT usuarios_rol_check CHECK (rol IN ('admin', 'empleado', 'dueno'))");

        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rol_id');
        });

        Schema::dropIfExists('roles');
    }
};
