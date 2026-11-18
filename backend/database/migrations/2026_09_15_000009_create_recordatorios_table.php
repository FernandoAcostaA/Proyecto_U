<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recordatorios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cita_id')->constrained('citas')->cascadeOnDelete();
            $table->string('canal', 20)->default('whatsapp');
            $table->timestamp('fecha_envio_programada');
            $table->timestamp('fecha_envio_real')->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->timestamps();

            $table->index(['estado', 'fecha_envio_programada']);
        });

        DB::statement("ALTER TABLE recordatorios ADD CONSTRAINT recordatorios_estado_check CHECK (estado IN ('pendiente', 'enviado', 'error'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('recordatorios');
    }
};
