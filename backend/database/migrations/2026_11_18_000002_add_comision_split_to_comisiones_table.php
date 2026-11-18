<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comisiones', function (Blueprint $table) {
            $table->decimal('porcentaje_barbero', 5, 2)->default(85)->after('monto');
            $table->decimal('porcentaje_barberia', 5, 2)->default(15)->after('porcentaje_barbero');
            $table->decimal('monto_barberia', 10, 2)->default(0)->after('porcentaje_barberia');
        });

        DB::statement('ALTER TABLE servicios ALTER COLUMN porcentaje_comision SET DEFAULT 85');
        DB::statement('UPDATE servicios SET porcentaje_comision = 85 WHERE porcentaje_comision = 0');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE servicios ALTER COLUMN porcentaje_comision SET DEFAULT 0');

        Schema::table('comisiones', function (Blueprint $table) {
            $table->dropColumn(['porcentaje_barbero', 'porcentaje_barberia', 'monto_barberia']);
        });
    }
};
