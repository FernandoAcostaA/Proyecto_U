<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreClienteRequest;
use App\Http\Resources\V1\ClienteResource;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;

class ClienteController extends Controller
{
    public function index(): JsonResponse
    {
        $clientes = Cliente::withCount('citas')->latest()->paginate();

        return ClienteResource::collection($clientes)->response();
    }

    public function store(StoreClienteRequest $request): JsonResponse
    {
        $cliente = Cliente::create($request->safe()->except('consentimiento'));

        if ($request->boolean('consentimiento')) {
            $cliente->aceptarConsentimiento();
        }

        return (new ClienteResource($cliente))->response()->setStatusCode(201);
    }

    public function show(Cliente $cliente): JsonResponse
    {
        return (new ClienteResource($cliente->loadCount('citas')))->response();
    }

    public function update(StoreClienteRequest $request, Cliente $cliente): JsonResponse
    {
        $cliente->update($request->safe()->except('consentimiento'));

        if ($request->boolean('consentimiento')) {
            $cliente->aceptarConsentimiento();
        }

        return (new ClienteResource($cliente))->response();
    }

    public function destroy(Cliente $cliente): JsonResponse
    {
        $cliente->delete();

        return response()->json(['message' => 'Cliente eliminado.']);
    }
}
