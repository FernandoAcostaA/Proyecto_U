<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\HorarioDisponibilidad;
use App\Models\Rol;
use App\Models\Servicio;
use App\Models\Usuario;

trait InteractuaConAgenda
{
    protected Empleado $empleado;

    protected Servicio $servicio;

    protected Cliente $cliente;

    protected Usuario $admin;

    protected function prepararAgenda(): void
    {
        $this->empleado = Empleado::create([
            'nombre' => 'Barbero Agenda',
            'telefono' => '900002801',
            'comision_base' => 40,
            'fecha_ingreso' => now()->subYear()->toDateString(),
        ]);

        $this->servicio = Servicio::create([
            'nombre' => 'Corte Agenda',
            'duracion_min' => 30,
            'precio' => 25,
            'porcentaje_comision' => 40,
        ]);

        $this->empleado->servicios()->attach($this->servicio->id);

        foreach (range(0, 6) as $dia) {
            HorarioDisponibilidad::create([
                'empleado_id' => $this->empleado->id,
                'dia_semana' => $dia,
                'hora_inicio' => '08:00:00',
                'hora_fin' => '20:00:00',
            ]);
        }

        $this->cliente = Cliente::create([
            'nombre' => 'Cliente Agenda',
            'telefono' => '900002802',
            'email' => 'cliente-agenda@test.com',
        ]);

        $this->admin = Usuario::create([
            'email' => 'admin-agenda@test.com',
            'password' => bcrypt('secret123'),
            'rol_id' => Rol::firstOrCreate([
                'nombre' => Rol::ADMIN,
            ], [
                'descripcion' => 'Administrador',
            ])->id,
        ]);
    }
}
