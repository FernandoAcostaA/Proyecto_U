<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->string('verificacion_token', 64)->nullable()->unique()->after('estado');
            $table->timestamp('verificacion_expira_en')->nullable()->after('verificacion_token');
        });

        DB::statement('ALTER TABLE citas ALTER COLUMN estado TYPE varchar(30)');

        DB::statement('ALTER TABLE citas DROP CONSTRAINT IF EXISTS citas_estado_check');
        DB::statement(
            'ALTER TABLE citas ADD CONSTRAINT citas_estado_check CHECK (estado IN '.
            "('pendiente', 'pendiente_verificacion', 'confirmada', 'completada', 'cancelada', 'no_asistio'))"
        );

        DB::statement('ALTER TABLE citas DROP CONSTRAINT IF EXISTS citas_empleado_no_overlap');
        DB::statement(
            'ALTER TABLE citas ADD CONSTRAINT citas_empleado_no_overlap '.
            'EXCLUDE USING gist (empleado_id WITH =, '.
            "tsrange(fecha_hora_inicio, fecha_hora_fin, '[)') WITH &&) ".
            "WHERE (estado IN ('pendiente', 'pendiente_verificacion', 'confirmada'))"
        );
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE citas DROP CONSTRAINT IF EXISTS citas_empleado_no_overlap');
        DB::statement(
            'ALTER TABLE citas ADD CONSTRAINT citas_empleado_no_overlap '.
            'EXCLUDE USING gist (empleado_id WITH =, '.
            "tsrange(fecha_hora_inicio, fecha_hora_fin, '[)') WITH &&) ".
            "WHERE (estado IN ('pendiente', 'confirmada'))"
        );

        DB::statement('ALTER TABLE citas DROP CONSTRAINT IF EXISTS citas_estado_check');
        DB::table('citas')
            ->where('estado', 'pendiente_verificacion')
            ->update(['estado' => 'cancelada']);
        DB::statement('ALTER TABLE citas ALTER COLUMN estado TYPE varchar(20)');
        DB::statement(
            'ALTER TABLE citas ADD CONSTRAINT citas_estado_check CHECK (estado IN '.
            "('pendiente', 'confirmada', 'completada', 'cancelada', 'no_asistio'))"
        );

        Schema::table('citas', function (Blueprint $table) {
            $table->dropUnique(['verificacion_token']);
            $table->dropColumn(['verificacion_token', 'verificacion_expira_en']);
        });
    }
};
