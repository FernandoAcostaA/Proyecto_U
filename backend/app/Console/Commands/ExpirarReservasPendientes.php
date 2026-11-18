<?php

namespace App\Console\Commands;

use App\Models\Cita;
use Illuminate\Console\Command;

class ExpirarReservasPendientes extends Command
{
    protected $signature = 'reservas:expirar';

    protected $description = 'Cancela las reservas publicas cuyo enlace de verificacion expiro.';

    public function handle(): int
    {
        $total = Cita::expirarVerificacionesPendientes();

        $this->info("Reservas expiradas: {$total}");

        return self::SUCCESS;
    }
}
