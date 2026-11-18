<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('empleado_id')->constrained('empleados')->restrictOnDelete();
            $table->foreignId('servicio_id')->constrained('servicios')->restrictOnDelete();
            $table->timestamp('fecha_hora_inicio');
            $table->timestamp('fecha_hora_fin');
            $table->string('estado', 20)->default('pendiente');
            $table->timestamps();

            $table->index(['empleado_id', 'fecha_hora_inicio']);
            $table->index(['fecha_hora_inicio', 'estado']);
        });

        DB::statement("ALTER TABLE citas ADD CONSTRAINT citas_estado_check CHECK (estado IN ('pendiente', 'confirmada', 'completada', 'cancelada', 'no_asistio'))");
        DB::statement('ALTER TABLE citas ADD CONSTRAINT citas_rango_check CHECK (fecha_hora_fin > fecha_hora_inicio)');

        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');
        DB::statement(
            'ALTER TABLE citas ADD CONSTRAINT citas_empleado_no_overlap '.
            'EXCLUDE USING gist (empleado_id WITH =, '.
            "tsrange(fecha_hora_inicio, fecha_hora_fin, '[)') WITH &&) ".
            "WHERE (estado IN ('pendiente', 'confirmada'))"
        );
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE citas DROP CONSTRAINT IF EXISTS citas_empleado_no_overlap');
        Schema::dropIfExists('citas');
    }
};
