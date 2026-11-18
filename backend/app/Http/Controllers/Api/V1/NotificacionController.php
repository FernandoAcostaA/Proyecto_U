<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\NotificacionResource;
use App\Models\Recordatorio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notificaciones = Recordatorio::with('cita')
            ->when($request->filled('cita_id'), fn ($q) => $q->where('cita_id', $request->integer('cita_id')))
            ->when($request->filled('tipo'), fn ($q) => $q->where('tipo', $request->string('tipo')))
            ->when($request->filled('canal'), fn ($q) => $q->where('canal', $request->string('canal')))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->string('estado')))
            ->latest()
            ->paginate();

        return NotificacionResource::collection($notificaciones)->response();
    }

    public function show(Recordatorio $notificacion): JsonResponse
    {
        return (new NotificacionResource($notificacion->load('cita')))->response();
    }
}
