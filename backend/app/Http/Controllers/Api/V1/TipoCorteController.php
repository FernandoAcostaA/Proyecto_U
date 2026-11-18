<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTipoCorteRequest;
use App\Http\Resources\V1\TipoCorteResource;
use App\Models\TipoCorte;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class TipoCorteController extends Controller
{
    public function index(): JsonResponse
    {
        $tipos = TipoCorte::latest()->paginate();

        return TipoCorteResource::collection($tipos)->response();
    }

    public function store(StoreTipoCorteRequest $request): JsonResponse
    {
        $data = $request->safe()->except(['imagen']);
        $data['activo'] = $data['activo'] ?? true;

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('tipos-corte', 'public');
        }

        $tipo = TipoCorte::create($data);

        return (new TipoCorteResource($tipo))->response()->setStatusCode(201);
    }

    public function show(TipoCorte $tipoCorte): JsonResponse
    {
        return (new TipoCorteResource($tipoCorte))->response();
    }

    public function update(StoreTipoCorteRequest $request, TipoCorte $tipoCorte): JsonResponse
    {
        $data = $request->safe()->except(['imagen']);

        if ($request->hasFile('imagen')) {
            if ($tipoCorte->imagen) {
                Storage::disk('public')->delete($tipoCorte->imagen);
            }

            $data['imagen'] = $request->file('imagen')->store('tipos-corte', 'public');
        }

        $tipoCorte->update($data);

        return (new TipoCorteResource($tipoCorte))->response();
    }

    public function destroy(TipoCorte $tipoCorte): JsonResponse
    {
        if ($tipoCorte->imagen) {
            Storage::disk('public')->delete($tipoCorte->imagen);
        }

        $tipoCorte->delete();

        return response()->json(['message' => 'Tipo de corte eliminado.']);
    }
}
