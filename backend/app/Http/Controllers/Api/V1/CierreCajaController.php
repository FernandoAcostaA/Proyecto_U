<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CierreCajaResource;
use App\Models\CierreCaja;
use App\Services\Caja\CierreCajaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CierreCajaController extends Controller
{
    public function __construct(protected CierreCajaService $service) {}

    public function resumen(Request $request): JsonResponse
    {
        $fecha = $this->fecha($request);

        return response()->json($this->service->resumen($fecha));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fecha' => ['nullable', 'date'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ]);

        $cierre = $this->service->cerrar(
            $this->fecha($request),
            $request->user(),
            $data['notas'] ?? null,
        );

        return (new CierreCajaResource($cierre->load('cerradoPor')))
            ->response()
            ->setStatusCode(201);
    }

    public function index(): JsonResponse
    {
        $cierres = CierreCaja::with('cerradoPor')->latest('fecha')->paginate();

        return CierreCajaResource::collection($cierres)->response();
    }

    public function show(CierreCaja $cierre): JsonResponse
    {
        return (new CierreCajaResource($cierre->load('cerradoPor')))->response();
    }

    protected function fecha(Request $request): Carbon
    {
        $fecha = $request->input('fecha');

        return $fecha ? Carbon::parse($fecha) : Carbon::today();
    }
}
