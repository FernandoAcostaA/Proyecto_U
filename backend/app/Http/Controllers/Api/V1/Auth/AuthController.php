<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\V1\ClienteResource;
use App\Http\Resources\V1\UsuarioResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(protected AuthService $service) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->service->register($request->validated());

        return response()->json([
            'message' => 'Registro exitoso.',
            'token' => $result['token'],
            'usuario' => new UsuarioResource($result['usuario']),
            'cliente' => new ClienteResource($result['cliente']),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->service->login($request->validated());

        return response()->json([
            'message' => 'Sesion iniciada.',
            'token' => $result['token'],
            'usuario' => new UsuarioResource($result['usuario']),
        ]);
    }

    public function logout(): JsonResponse
    {
        $this->service->logout(Auth::user());

        return response()->json([
            'message' => 'Sesion cerrada.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'usuario' => new UsuarioResource($request->user()->load('rol', 'cliente')),
        ]);
    }
}
