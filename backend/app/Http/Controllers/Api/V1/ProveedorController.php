<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProveedorRequest;
use App\Http\Resources\V1\ProveedorResource;
use App\Models\Proveedor;
use Illuminate\Http\JsonResponse;

class ProveedorController extends Controller
{
    public function index(): JsonResponse
    {
        $proveedores = Proveedor::withCount('productos')->latest()->paginate();

        return ProveedorResource::collection($proveedores)->response();
    }

    public function store(StoreProveedorRequest $request): JsonResponse
    {
        $proveedor = Proveedor::create($request->validated());

        return (new ProveedorResource($proveedor))->response()->setStatusCode(201);
    }

    public function show(Proveedor $proveedor): JsonResponse
    {
        return (new ProveedorResource($proveedor->loadCount('productos')))->response();
    }

    public function update(StoreProveedorRequest $request, Proveedor $proveedor): JsonResponse
    {
        $proveedor->update($request->validated());

        return (new ProveedorResource($proveedor))->response();
    }

    public function destroy(Proveedor $proveedor): JsonResponse
    {
        $proveedor->delete();

        return response()->json(['message' => 'Proveedor eliminado.']);
    }
}
