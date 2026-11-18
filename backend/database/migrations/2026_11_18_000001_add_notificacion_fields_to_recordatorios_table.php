<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recordatorios', function (Blueprint $table) {
            $table->string('tipo', 20)->default('recordatorio')->after('cita_id');
            $table->string('destino', 255)->nullable()->after('canal');
            $table->string('proveedor', 40)->nullable()->after('destino');
            $table->decimal('costo_estimado', 10, 4)->default(0)->after('proveedor');
            $table->string('asunto', 255)->nullable()->after('costo_estimado');
            $table->text('mensaje')->nullable()->after('asunto');
            $table->text('error')->nullable()->after('mensaje');
            $table->string('id_externo', 100)->nullable()->after('error');
            $table->string('respuesta_token', 64)->nullable()->unique()->after('id_externo');
            $table->timestamp('respondido_en')->nullable()->after('respuesta_token');
            $table->string('respuesta', 20)->nullable()->after('respondido_en');
            $table->jsonb('metadata')->nullable()->after('respuesta');
        });

        DB::statement('ALTER TABLE recordatorios DROP CONSTRAINT IF EXISTS recordatorios_estado_check');
        DB::statement("ALTER TABLE recordatorios ADD CONSTRAINT recordatorios_estado_check CHECK (estado IN ('pendiente', 'enviado', 'error', 'cancelado'))");
        DB::statement("ALTER TABLE recordatorios ADD CONSTRAINT recordatorios_tipo_check CHECK (tipo IN ('confirmacion', 'recordatorio', 'verificacion'))");
        DB::statement("ALTER TABLE recordatorios ADD CONSTRAINT recordatorios_respuesta_check CHECK (respuesta IS NULL OR respuesta IN ('confirmar', 'cancelar'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE recordatorios DROP CONSTRAINT IF EXISTS recordatorios_respuesta_check');
        DB::statement('ALTER TABLE recordatorios DROP CONSTRAINT IF EXISTS recordatorios_tipo_check');
        DB::statement('ALTER TABLE recordatorios DROP CONSTRAINT IF EXISTS recordatorios_estado_check');
        DB::statement("ALTER TABLE recordatorios ADD CONSTRAINT recordatorios_estado_check CHECK (estado IN ('pendiente', 'enviado', 'error'))");

        Schema::table('recordatorios', function (Blueprint $table) {
            $table->dropUnique(['respuesta_token']);
            $table->dropColumn([
                'tipo',
                'destino',
                'proveedor',
                'costo_estimado',
                'asunto',
                'mensaje',
                'error',
                'id_externo',
                'respuesta_token',
                'respondido_en',
                'respuesta',
                'metadata',
            ]);
        });
    }
};
