<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreServicioRequest;
use App\Http\Resources\V1\ServicioResource;
use App\Models\Servicio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ServicioController extends Controller
{
    public function index(): JsonResponse
    {
        $servicios = Servicio::with(['empleados', 'productos'])
            ->withAvg('calificaciones', 'estrellas')
            ->withCount('calificaciones')
            ->latest()
            ->paginate();

        return ServicioResource::collection($servicios)->response();
    }

    public function store(StoreServicioRequest $request): JsonResponse
    {
        $data = $request->safe()->except(['empleados', 'productos', 'imagen']);

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('servicios', 'public');
        }

        $servicio = Servicio::create($data);

        if ($request->filled('empleados')) {
            $servicio->empleados()->sync($request->input('empleados'));
        }

        if ($request->filled('productos')) {
            $this->reemplazarProductos($servicio, $request->input('productos'));
        }

        return (new ServicioResource($this->cargar($servicio)))->response()->setStatusCode(201);
    }

    public function show(Servicio $servicio): JsonResponse
    {
        return (new ServicioResource($this->cargar($servicio)))->response();
    }

    public function update(StoreServicioRequest $request, Servicio $servicio): JsonResponse
    {
        $data = $request->safe()->except(['empleados', 'productos', 'imagen']);

        if ($request->hasFile('imagen')) {
            if ($servicio->imagen) {
                Storage::disk('public')->delete($servicio->imagen);
            }

            $data['imagen'] = $request->file('imagen')->store('servicios', 'public');
        }

        $servicio->update($data);

        if ($request->filled('empleados')) {
            $servicio->empleados()->sync($request->input('empleados'));
        }

        if ($request->filled('productos')) {
            $this->reemplazarProductos($servicio, $request->input('productos'));
        }

        return (new ServicioResource($this->cargar($servicio)))->response();
    }

    public function destroy(Servicio $servicio): JsonResponse
    {
        if ($servicio->imagen) {
            Storage::disk('public')->delete($servicio->imagen);
        }

        $servicio->delete();

        return response()->json(['message' => 'Servicio eliminado.']);
    }

    /**
     * Define qué empleados están capacitados para dar este servicio.
     */
    public function syncEmpleados(Request $request, Servicio $servicio): JsonResponse
    {
        $request->validate([
            'empleados' => ['required', 'array'],
            'empleados.*' => ['integer', 'exists:empleados,id'],
        ]);

        $servicio->empleados()->sync($request->input('empleados'));

        return (new ServicioResource($this->cargar($servicio)))->response();
    }

    /**
     * Define qué productos consume el servicio y en qué cantidad.
     */
    public function syncProductos(Request $request, Servicio $servicio): JsonResponse
    {
        $request->validate([
            'productos' => ['required', 'array'],
            'productos.*.producto_id' => ['required', 'integer', 'exists:productos,id'],
            'productos.*.cantidad_usada' => ['required', 'integer', 'min:1'],
        ]);

        $this->reemplazarProductos($servicio, $request->input('productos'));

        return (new ServicioResource($this->cargar($servicio)))->response();
    }

    protected function cargar(Servicio $servicio): Servicio
    {
        return $servicio->load(['empleados', 'productos'])
            ->loadAvg('calificaciones', 'estrellas')
            ->loadCount('calificaciones');
    }

    protected function reemplazarProductos(Servicio $servicio, array $productos): void
    {
        $pivot = [];
        foreach ($productos as $producto) {
            $pivot[$producto['producto_id']] = [
                'cantidad_usada' => $producto['cantidad_usada'],
            ];
        }

        $servicio->productos()->sync($pivot);
    }
}
