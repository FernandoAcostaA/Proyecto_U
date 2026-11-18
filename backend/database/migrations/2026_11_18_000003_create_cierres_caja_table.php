<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cierres_caja', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->unique();
            $table->unsignedInteger('cantidad_pagos')->default(0);
            $table->unsignedInteger('cantidad_citas')->default(0);
            $table->decimal('total_cobrado', 10, 2)->default(0);
            $table->decimal('total_comisiones', 10, 2)->default(0);
            $table->decimal('total_barberia', 10, 2)->default(0);
            $table->decimal('total_pendiente_comisiones', 10, 2)->default(0);
            $table->jsonb('detalle')->nullable();
            $table->text('notas')->nullable();
            $table->foreignId('cerrado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamps();

            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cierres_caja');
    }
};
