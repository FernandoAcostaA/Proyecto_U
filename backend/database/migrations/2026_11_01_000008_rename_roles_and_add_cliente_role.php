<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $adminId = DB::table('roles')->where('nombre', 'admin')->value('id');
        $duenoId = DB::table('roles')->where('nombre', 'dueno')->value('id');

        if ($duenoId && $adminId) {
            DB::table('usuarios')->where('rol_id', $duenoId)->update(['rol_id' => $adminId]);
            DB::table('roles')->where('id', $duenoId)->delete();
        }

        DB::table('roles')
            ->where('nombre', 'empleado')
            ->update([
                'nombre' => 'trabajador',
                'descripcion' => 'Trabajador/barbero, agenda y atencion de citas.',
                'updated_at' => $now,
            ]);

        DB::table('roles')->updateOrInsert(
            ['nombre' => 'cliente'],
            [
                'descripcion' => 'Cliente final, reserva y consulta sus citas.',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('roles')
            ->where('nombre', 'admin')
            ->update([
                'descripcion' => 'Dueno/administrador, acceso total y reportes.',
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        $now = now();

        DB::table('roles')
            ->where('nombre', 'trabajador')
            ->update([
                'nombre' => 'empleado',
                'descripcion' => 'Trabajador/barbero, agenda y atencion de citas.',
                'updated_at' => $now,
            ]);

        DB::table('roles')->updateOrInsert(
            ['nombre' => 'dueno'],
            [
                'descripcion' => 'Dueno de la barberia, acceso total.',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $empleadoId = DB::table('roles')->where('nombre', 'empleado')->value('id');
        $clienteId = DB::table('roles')->where('nombre', 'cliente')->value('id');

        if ($clienteId && $empleadoId) {
            DB::table('usuarios')->where('rol_id', $clienteId)->update(['rol_id' => $empleadoId]);
            DB::table('roles')->where('id', $clienteId)->delete();
        }
    }
};
