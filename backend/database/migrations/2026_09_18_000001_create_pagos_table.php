<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cita_id')->constrained('citas')->cascadeOnDelete()->unique();
            $table->decimal('monto', 10, 2);
            $table->string('metodo', 20)->default('efectivo');
            $table->string('estado', 20)->default('pagado');
            $table->timestamp('pagado_en')->nullable();
            $table->timestamps();

            $table->index(['estado', 'pagado_en']);
        });

        DB::statement("ALTER TABLE pagos ADD CONSTRAINT pagos_metodo_check CHECK (metodo IN ('efectivo', 'tarjeta', 'transferencia', 'otro'))");
        DB::statement("ALTER TABLE pagos ADD CONSTRAINT pagos_estado_check CHECK (estado IN ('pendiente', 'pagado', 'reembolsado'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
