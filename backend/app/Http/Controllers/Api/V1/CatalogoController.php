<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\EmpleadoResource;
use App\Http\Resources\V1\ServicioResource;
use App\Http\Resources\V1\TipoCorteResource;
use App\Models\Empleado;
use App\Models\Servicio;
use App\Models\TipoCorte;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;

/**
 * Catalogo publico (sin autenticacion) que consume el cliente final:
 * tipos de corte, servicios y barberos con foto y calificacion promedio.
 */
class CatalogoController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'negocio' => [
                'nombre' => config('app.name'),
            ],
            'tipos_corte' => TipoCorteResource::collection($this->tiposCorteActivos())->resolve(),
            'servicios' => ServicioResource::collection($this->serviciosConPromedio())->resolve(),
            'barberos' => EmpleadoResource::collection($this->barberosConPromedio())->resolve(),
        ]);
    }

    public function tiposCorte(): JsonResponse
    {
        return TipoCorteResource::collection($this->tiposCorteActivos())->response();
    }

    public function servicios(): JsonResponse
    {
        return ServicioResource::collection($this->serviciosConPromedio())->response();
    }

    public function barberos(): JsonResponse
    {
        return EmpleadoResource::collection($this->barberosConPromedio())->response();
    }

    protected function tiposCorteActivos(): Collection
    {
        return TipoCorte::where('activo', true)
            ->orderBy('nombre')
            ->get();
    }

    protected function serviciosConPromedio(): Collection
    {
        return Servicio::withAvg('calificaciones', 'estrellas')
            ->withCount('calificaciones')
            ->orderBy('nombre')
            ->get();
    }

    protected function barberosConPromedio(): Collection
    {
        return Empleado::with(['servicios'])
            ->withAvg('calificaciones', 'estrellas')
            ->withCount('calificaciones')
            ->orderBy('nombre')
            ->get();
    }
}
