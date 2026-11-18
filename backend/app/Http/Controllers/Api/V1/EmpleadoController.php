<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreEmpleadoRequest;
use App\Http\Resources\V1\EmpleadoResource;
use App\Models\Empleado;
use App\Models\HorarioDisponibilidad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmpleadoController extends Controller
{
    public function index(): JsonResponse
    {
        $empleados = Empleado::with(['servicios', 'horariosDisponibilidad'])
            ->withAvg('calificaciones', 'estrellas')
            ->withCount('calificaciones')
            ->latest()
            ->paginate();

        return EmpleadoResource::collection($empleados)->response();
    }

    public function store(StoreEmpleadoRequest $request): JsonResponse
    {
        $empleado = Empleado::create($request->safe()->except(['servicios', 'horarios']));

        if ($request->filled('servicios')) {
            $empleado->servicios()->sync($request->input('servicios'));
        }

        if ($request->filled('horarios')) {
            $this->reemplazarHorarios($empleado, $request->input('horarios'));
        }

        return (new EmpleadoResource($this->cargar($empleado)))->response()->setStatusCode(201);
    }

    public function show(Empleado $empleado): JsonResponse
    {
        return (new EmpleadoResource($this->cargar($empleado)))->response();
    }

    public function update(StoreEmpleadoRequest $request, Empleado $empleado): JsonResponse
    {
        $empleado->update($request->safe()->except(['servicios', 'horarios']));

        if ($request->filled('servicios')) {
            $empleado->servicios()->sync($request->input('servicios'));
        }

        if ($request->filled('horarios')) {
            $this->reemplazarHorarios($empleado, $request->input('horarios'));
        }

        return (new EmpleadoResource($this->cargar($empleado)))->response();
    }

    public function destroy(Empleado $empleado): JsonResponse
    {
        $empleado->delete();

        return response()->json(['message' => 'Empleado eliminado.']);
    }

    /**
     * Asigna qué servicios es capaz de brindar el empleado.
     */
    public function syncServicios(Request $request, Empleado $empleado): JsonResponse
    {
        $request->validate([
            'servicios' => ['required', 'array'],
            'servicios.*' => ['integer', 'exists:servicios,id'],
        ]);

        $empleado->servicios()->sync($request->input('servicios'));

        return (new EmpleadoResource($this->cargar($empleado)))->response();
    }

    /**
     * Reemplaza por completo la disponibilidad semanal del empleado.
     */
    public function syncHorarios(Request $request, Empleado $empleado): JsonResponse
    {
        $request->validate([
            'horarios' => ['required', 'array'],
            'horarios.*.dia_semana' => ['required', 'integer', 'between:0,6'],
            'horarios.*.hora_inicio' => ['required', 'date_format:H:i'],
            'horarios.*.hora_fin' => ['required', 'date_format:H:i', 'after:horarios.*.hora_inicio'],
        ]);

        $this->reemplazarHorarios($empleado, $request->input('horarios'));

        return (new EmpleadoResource($this->cargar($empleado)))->response();
    }

    /**
     * Sube o reemplaza la foto del barbero (multipart/form-data, campo "foto").
     */
    public function subirFoto(Request $request, Empleado $empleado): JsonResponse
    {
        $request->validate([
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        if ($empleado->foto) {
            Storage::disk('public')->delete($empleado->foto);
        }

        $empleado->update([
            'foto' => $request->file('foto')->store('empleados', 'public'),
        ]);

        return (new EmpleadoResource($this->cargar($empleado)))->response();
    }

    public function eliminarFoto(Empleado $empleado): JsonResponse
    {
        if ($empleado->foto) {
            Storage::disk('public')->delete($empleado->foto);
        }

        $empleado->update(['foto' => null]);

        return (new EmpleadoResource($this->cargar($empleado)))->response();
    }

    protected function cargar(Empleado $empleado): Empleado
    {
        return $empleado->load(['servicios', 'horariosDisponibilidad'])
            ->loadAvg('calificaciones', 'estrellas')
            ->loadCount('calificaciones');
    }

    protected function reemplazarHorarios(Empleado $empleado, array $horarios): void
    {
        $empleado->horariosDisponibilidad()->delete();

        foreach ($horarios as $horario) {
            HorarioDisponibilidad::create([
                'empleado_id' => $empleado->id,
                'dia_semana' => $horario['dia_semana'],
                'hora_inicio' => $horario['hora_inicio'],
                'hora_fin' => $horario['hora_fin'],
            ]);
        }
    }
}
