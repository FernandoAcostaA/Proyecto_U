<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProductoRequest;
use App\Http\Resources\V1\ProductoResource;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;

class ProductoController extends Controller
{
    public function index(): JsonResponse
    {
        $productos = Producto::with('proveedor')->latest()->paginate();

        return ProductoResource::collection($productos)->response();
    }

    public function store(StoreProductoRequest $request): JsonResponse
    {
        $producto = Producto::create($request->validated());

        return (new ProductoResource($producto))->response()->setStatusCode(201);
    }

    public function show(Producto $producto): JsonResponse
    {
        return (new ProductoResource($producto->load('proveedor')))->response();
    }

    public function update(StoreProductoRequest $request, Producto $producto): JsonResponse
    {
        $producto->update($request->validated());

        return (new ProductoResource($producto->load('proveedor')))->response();
    }

    public function destroy(Producto $producto): JsonResponse
    {
        $producto->delete();

        return response()->json(['message' => 'Producto eliminado.']);
    }

    /**
     * Alerta de stock bajo: stock_actual <= stock_minimo.
     */
    public function stockBajo(): JsonResponse
    {
        $productos = Producto::stockBajo()->with('proveedor')->get();

        return ProductoResource::collection($productos)->response();
    }
}
