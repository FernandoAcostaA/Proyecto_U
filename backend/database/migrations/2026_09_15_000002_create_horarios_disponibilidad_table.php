<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horarios_disponibilidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $table->unsignedSmallInteger('dia_semana');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->timestamps();

            $table->index(['empleado_id', 'dia_semana']);
        });

        DB::statement('ALTER TABLE horarios_disponibilidad ADD CONSTRAINT horarios_dia_semana_check CHECK (dia_semana BETWEEN 0 AND 6)');
        DB::statement('ALTER TABLE horarios_disponibilidad ADD CONSTRAINT horarios_rango_check CHECK (hora_fin > hora_inicio)');
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios_disponibilidad');
    }
};
