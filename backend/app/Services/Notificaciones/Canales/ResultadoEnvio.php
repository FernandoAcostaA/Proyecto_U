<?php

namespace App\Services\Notificaciones\Canales;

final class ResultadoEnvio
{
    public function __construct(
        public readonly string $proveedor,
        public readonly ?string $idExterno = null,
        public readonly float $costo = 0.0,
    ) {}
}
