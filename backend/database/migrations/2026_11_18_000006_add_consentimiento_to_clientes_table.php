<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->boolean('consentimiento_aceptado')->default(false)->after('email');
            $table->timestamp('consentimiento_en')->nullable()->after('consentimiento_aceptado');
            $table->string('consentimiento_version', 20)->nullable()->after('consentimiento_en');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['consentimiento_aceptado', 'consentimiento_en', 'consentimiento_version']);
        });
    }
};
