<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE citas ALTER COLUMN estado TYPE varchar(30)');
    }

    public function down(): void
    {
        DB::table('citas')
            ->where('estado', 'pendiente_verificacion')
            ->update(['estado' => 'cancelada']);

        DB::statement('ALTER TABLE citas ALTER COLUMN estado TYPE varchar(20)');
    }
};
