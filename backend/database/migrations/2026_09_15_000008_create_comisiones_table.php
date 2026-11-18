<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comisiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cita_id')->constrained('citas')->cascadeOnDelete()->unique();
            $table->foreignId('empleado_id')->constrained('empleados')->restrictOnDelete();
            $table->decimal('monto', 10, 2);
            $table->string('estado_pago', 20)->default('pendiente');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE comisiones ADD CONSTRAINT comisiones_estado_check CHECK (estado_pago IN ('pendiente', 'pagado'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('comisiones');
    }
};
