<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('cita_id')->nullable()->constrained('citas')->nullOnDelete();
            $table->string('tipo', 10);
            $table->unsignedInteger('cantidad');
            $table->timestamps();

            $table->index(['producto_id', 'tipo']);
        });

        DB::statement("ALTER TABLE movimientos_stock ADD CONSTRAINT movimientos_tipo_check CHECK (tipo IN ('entrada', 'salida'))");
        DB::statement('ALTER TABLE movimientos_stock ADD CONSTRAINT movimientos_cantidad_check CHECK (cantidad > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_stock');
    }
};
