<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cita_id')->unique()->constrained('citas')->cascadeOnDelete();
            $table->foreignId('empleado_id')->constrained('empleados')->restrictOnDelete();
            $table->foreignId('servicio_id')->constrained('servicios')->restrictOnDelete();
            $table->unsignedSmallInteger('estrellas');
            $table->text('comentario')->nullable();
            $table->string('origen', 10)->default('panel');
            $table->timestamps();

            $table->index('empleado_id');
            $table->index('servicio_id');
        });

        DB::statement('ALTER TABLE calificaciones ADD CONSTRAINT calificaciones_estrellas_check CHECK (estrellas BETWEEN 1 AND 5)');
        DB::statement("ALTER TABLE calificaciones ADD CONSTRAINT calificaciones_origen_check CHECK (origen IN ('panel', 'cliente'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('calificaciones');
    }
};
