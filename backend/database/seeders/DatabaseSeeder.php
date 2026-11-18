<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\HorarioDisponibilidad;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Rol;
use App\Models\Servicio;
use App\Models\TipoCorte;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Datos demo de Blackhound (barberia unica).
 *
 * Deja IDs estables que usan los ejemplos de la documentacion y la suite
 * de regresion: empleado 7 (Marco Diaz), servicio 4 (Corte fade premium)
 * y producto 1 (Shampoo profesional 1L).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $roles = $this->roles();

            $this->usuarioDemo('admin@blackhound.test', $roles[Rol::ADMIN]);
            Usuario::where('email', 'dueno@blackhound.test')->delete();

            $trabajador = $this->usuarioDemo('barbero@blackhound.test', $roles[Rol::TRABAJADOR]);
            $clienteUsuario = $this->usuarioDemo('cliente@blackhound.test', $roles[Rol::CLIENTE]);

            if (Cliente::query()->doesntExist()) {
                $this->datosDemo($trabajador);
            }

            $this->vincularUsuarios($trabajador, $clienteUsuario);
        });
    }

    /**
     * @return array<string, Rol>
     */
    protected function roles(): array
    {
        $descripciones = [
            Rol::ADMIN => 'Dueno/administrador, acceso total y reportes.',
            Rol::TRABAJADOR => 'Trabajador/barbero, agenda y atencion de citas.',
            Rol::CLIENTE => 'Cliente final, reserva y consulta sus citas.',
        ];

        $roles = [];
        foreach ($descripciones as $nombre => $descripcion) {
            $roles[$nombre] = Rol::updateOrCreate(
                ['nombre' => $nombre],
                ['descripcion' => $descripcion],
            );
        }

        return $roles;
    }

    protected function usuarioDemo(string $email, Rol $rol): Usuario
    {
        $usuario = Usuario::firstOrCreate(
            ['email' => $email],
            ['password' => 'secret123', 'rol_id' => $rol->id],
        );

        if ($usuario->rol_id !== $rol->id) {
            $usuario->update(['rol_id' => $rol->id]);
        }

        return $usuario;
    }

    protected function datosDemo(Usuario $trabajador): void
    {
        $proveedores = collect([
            ['nombre' => "Distribuidora L'Oreal", 'telefono' => '60011111'],
            ['nombre' => 'Beauty Supply Bolivia', 'telefono' => '60022222'],
            ['nombre' => 'Importadora Andina', 'telefono' => '60033333'],
            ['nombre' => 'Barber Tools SRL', 'telefono' => '60044444'],
            ['nombre' => 'Estilo Profesional', 'telefono' => '60055555'],
        ])->map(fn (array $data) => Proveedor::create($data));

        $productos = collect([
            ['proveedor_id' => $proveedores[0]->id, 'nombre' => 'Shampoo profesional 1L', 'stock_actual' => 7, 'stock_minimo' => 2],
            ['proveedor_id' => $proveedores[1]->id, 'nombre' => 'Acondicionador 500ml', 'stock_actual' => 0, 'stock_minimo' => 3],
            ['proveedor_id' => $proveedores[2]->id, 'nombre' => 'Cera moldeadora', 'stock_actual' => 15, 'stock_minimo' => 5],
            ['proveedor_id' => $proveedores[0]->id, 'nombre' => 'Tinte negro', 'stock_actual' => 8, 'stock_minimo' => 4],
            ['proveedor_id' => $proveedores[3]->id, 'nombre' => 'Navajas desechables', 'stock_actual' => 20, 'stock_minimo' => 10],
        ])->map(fn (array $data) => Producto::create($data));

        $servicios = collect([
            ['nombre' => 'Corte clasico', 'duracion_min' => 30, 'precio' => 25.00, 'porcentaje_comision' => 85],
            ['nombre' => 'Corte + barba', 'duracion_min' => 45, 'precio' => 40.00, 'porcentaje_comision' => 85],
            ['nombre' => 'Tinte completo', 'duracion_min' => 90, 'precio' => 80.00, 'porcentaje_comision' => 85],
            ['nombre' => 'Corte fade premium', 'duracion_min' => 40, 'precio' => 55.50, 'porcentaje_comision' => 85],
        ])->map(fn (array $data) => Servicio::create($data));

        $empleados = collect([
            ['nombre' => 'Carlos Rojas', 'telefono' => '70010001', 'comision_base' => 35, 'fecha_ingreso' => '2024-03-01'],
            ['nombre' => 'Luis Fernandez', 'telefono' => '70010002', 'comision_base' => 35, 'fecha_ingreso' => '2024-05-10'],
            ['nombre' => 'Andrea Salazar', 'telefono' => '70010003', 'comision_base' => 40, 'fecha_ingreso' => '2024-08-20'],
            ['nombre' => 'Diego Vargas', 'telefono' => '70010004', 'comision_base' => 35, 'fecha_ingreso' => '2025-01-05'],
            ['nombre' => 'Sofia Mendoza', 'telefono' => '70010005', 'comision_base' => 40, 'fecha_ingreso' => '2025-02-17'],
            ['nombre' => 'Jorge Castro', 'telefono' => '70010006', 'comision_base' => 30, 'fecha_ingreso' => '2025-04-02'],
            ['nombre' => 'Marco Diaz', 'telefono' => '70010007', 'comision_base' => 10, 'fecha_ingreso' => '2025-06-15'],
        ])->map(fn (array $data) => Empleado::create($data));

        $empleados[0]->update(['usuario_id' => $trabajador->id]);

        collect([
            ['nombre' => 'Ana Perez', 'telefono' => '71110001', 'email' => 'ana.perez@test.com'],
            ['nombre' => 'Bruno Quispe', 'telefono' => '71110002', 'email' => 'bruno.quispe@test.com'],
            ['nombre' => 'Carla Flores', 'telefono' => '71110003', 'email' => 'carla.flores@test.com'],
            ['nombre' => 'Daniel Ortiz', 'telefono' => '71110004', 'email' => 'daniel.ortiz@test.com'],
            ['nombre' => 'Elena Ramos', 'telefono' => '71110005', 'email' => 'elena.ramos@test.com'],
        ])->each(fn (array $data) => Cliente::create($data));

        collect([
            ['nombre' => 'Corte clasico', 'descripcion' => 'Tijera y maquina, acabado natural.'],
            ['nombre' => 'Fade', 'descripcion' => 'Degradado alto, medio o bajo.'],
            ['nombre' => 'Corte + barba', 'descripcion' => 'Corte personalizado y perfilado de barba.'],
            ['nombre' => 'Diseno de barba', 'descripcion' => 'Perfilado y arreglo de barba.'],
        ])->each(fn (array $data) => TipoCorte::create($data));

        $this->asignarServicios($empleados, $servicios);
        $this->asignarConsumos($servicios, $productos);
        $this->asignarHorarios($empleados);
    }

    protected function vincularUsuarios(Usuario $trabajador, Usuario $clienteUsuario): void
    {
        $empleado = Empleado::where('nombre', 'Carlos Rojas')->first();

        if ($empleado && ! $empleado->usuario_id) {
            $empleado->update(['usuario_id' => $trabajador->id]);
        }

        $cliente = Cliente::orderBy('id')->first();

        if ($cliente && ! $cliente->usuario_id) {
            $cliente->update(['usuario_id' => $clienteUsuario->id]);
        }
    }

    protected function asignarServicios($empleados, $servicios): void
    {
        $mapa = [
            0 => [0, 1, 3],
            1 => [0, 1],
            2 => [0, 2, 3],
            3 => [0, 1, 3],
            4 => [0, 2],
            5 => [0, 1],
            6 => [1, 3],
        ];

        foreach ($mapa as $indiceEmpleado => $indicesServicios) {
            $empleados[$indiceEmpleado]->servicios()->attach(
                collect($indicesServicios)->map(fn (int $i) => $servicios[$i]->id)->all()
            );
        }
    }

    protected function asignarConsumos($servicios, $productos): void
    {
        $servicios[1]->productos()->attach($productos[1]->id, ['cantidad_usada' => 1]);
        $servicios[2]->productos()->attach($productos[3]->id, ['cantidad_usada' => 1]);
        $servicios[3]->productos()->attach($productos[0]->id, ['cantidad_usada' => 1]);
    }

    protected function asignarHorarios($empleados): void
    {
        foreach ($empleados as $indice => $empleado) {
            if ($indice === 6) {
                HorarioDisponibilidad::create([
                    'empleado_id' => $empleado->id,
                    'dia_semana' => HorarioDisponibilidad::LUNES,
                    'hora_inicio' => '09:00:00',
                    'hora_fin' => '13:00:00',
                ]);

                foreach ([2, 3, 4, 5, 6] as $dia) {
                    HorarioDisponibilidad::create([
                        'empleado_id' => $empleado->id,
                        'dia_semana' => $dia,
                        'hora_inicio' => '09:00:00',
                        'hora_fin' => '18:00:00',
                    ]);
                }

                continue;
            }

            foreach ([1, 2, 3, 4, 5, 6] as $dia) {
                HorarioDisponibilidad::create([
                    'empleado_id' => $empleado->id,
                    'dia_semana' => $dia,
                    'hora_inicio' => '09:00:00',
                    'hora_fin' => '18:00:00',
                ]);
            }
        }
    }
}
