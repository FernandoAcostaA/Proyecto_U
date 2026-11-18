<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->foreignId('usuario_id')
                ->nullable()
                ->unique()
                ->after('id')
                ->constrained('usuarios')
                ->nullOnDelete();

            $table->unique('telefono');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropUnique(['telefono']);
            $table->dropConstrainedForeignId('usuario_id');
        });
    }
};
