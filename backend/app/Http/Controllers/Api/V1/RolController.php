<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Rol;
use Illuminate\Http\JsonResponse;

class RolController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = Rol::orderBy('nombre')->get(['id', 'nombre', 'descripcion']);

        return response()->json(['data' => $roles]);
    }
}
