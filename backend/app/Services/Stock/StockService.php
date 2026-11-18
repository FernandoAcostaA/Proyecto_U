<?php

namespace App\Services\Stock;

use App\Models\Cita;
use App\Models\MovimientoStock;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Movimientos de stock y actualizacion de existencias.
 */
class StockService
{
    public function entrada(Producto $producto, int $cantidad): MovimientoStock
    {
        return DB::transaction(function () use ($producto, $cantidad) {
            $producto->increment('stock_actual', $cantidad);

            return MovimientoStock::create([
                'producto_id' => $producto->id,
                'tipo' => MovimientoStock::TIPO_ENTRADA,
                'cantidad' => $cantidad,
            ]);
        });
    }

    public function salida(Producto $producto, int $cantidad, ?Cita $cita = null): MovimientoStock
    {
        if ($producto->stock_actual < $cantidad) {
            throw ValidationException::withMessages([
                'producto' => "Stock insuficiente para el producto «{$producto->nombre}» (disponible: {$producto->stock_actual}).",
            ]);
        }

        return DB::transaction(function () use ($producto, $cantidad, $cita) {
            $producto->decrement('stock_actual', $cantidad);

            return MovimientoStock::create([
                'producto_id' => $producto->id,
                'cita_id' => $cita?->id,
                'tipo' => MovimientoStock::TIPO_SALIDA,
                'cantidad' => $cantidad,
            ]);
        });
    }
}
