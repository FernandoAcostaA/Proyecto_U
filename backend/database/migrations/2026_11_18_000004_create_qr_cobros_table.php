<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_cobros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cita_id')->constrained('citas')->cascadeOnDelete();
            $table->foreignId('pago_id')->nullable()->constrained('pagos')->nullOnDelete();
            $table->string('referencia', 64)->unique();
            $table->text('payload');
            $table->decimal('monto', 10, 2);
            $table->string('estado', 20)->default('pendiente');
            $table->timestamp('expira_en')->nullable();
            $table->timestamp('confirmado_en')->nullable();
            $table->foreignId('confirmado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['cita_id', 'estado']);
            $table->index(['estado', 'expira_en']);
        });

        DB::statement("ALTER TABLE qr_cobros ADD CONSTRAINT qr_cobros_estado_check CHECK (estado IN ('pendiente', 'confirmado', 'expirado', 'anulado'))");

        DB::statement('ALTER TABLE pagos DROP CONSTRAINT IF EXISTS pagos_metodo_check');
        DB::statement("ALTER TABLE pagos ADD CONSTRAINT pagos_metodo_check CHECK (metodo IN ('efectivo', 'tarjeta', 'transferencia', 'qr', 'otro'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE pagos DROP CONSTRAINT IF EXISTS pagos_metodo_check');
        DB::statement("ALTER TABLE pagos ADD CONSTRAINT pagos_metodo_check CHECK (metodo IN ('efectivo', 'tarjeta', 'transferencia', 'otro'))");

        Schema::dropIfExists('qr_cobros');
    }
};
