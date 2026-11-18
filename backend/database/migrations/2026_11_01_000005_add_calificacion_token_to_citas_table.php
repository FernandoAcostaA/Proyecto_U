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
            $table->uuid('calificacion_token')->nullable()->unique()->after('estado');
        });

        DB::statement(
            'UPDATE citas SET calificacion_token = gen_random_uuid() '.
            "WHERE estado = 'completada' AND calificacion_token IS NULL"
        );
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropUnique(['calificacion_token']);
            $table->dropColumn('calificacion_token');
        });
    }
};
