<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\QrCobroResource;
use App\Models\Cita;
use App\Models\QrCobro;
use App\Services\Qr\QrCobroService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QrCobroController extends Controller
{
    public function __construct(protected QrCobroService $service) {}

    public function generar(Request $request, Cita $cita): JsonResponse
    {
        $resultado = $this->service->generar($cita, $request->user());

        return response()->json([
            'data' => new QrCobroResource($resultado['qr']),
            'svg' => $resultado['svg'],
        ], 201);
    }

    public function imagen(QrCobro $qrCobro)
    {
        return response($this->service->svg($qrCobro->payload))
            ->header('Content-Type', 'image/svg+xml');
    }

    public function confirmar(Request $request, string $referencia): JsonResponse
    {
        $data = $request->validate([
            'monto' => ['nullable', 'numeric', 'min:0'],
            'id_externo' => ['nullable', 'string', 'max:100'],
        ]);

        $qr = $this->service->confirmar(
            $referencia,
            isset($data['monto']) ? (float) $data['monto'] : null,
            $data['id_externo'] ?? null,
        );

        return response()->json([
            'message' => 'Cobro por QR confirmado.',
            'data' => new QrCobroResource($qr),
        ]);
    }
}
