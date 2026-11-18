<?php

namespace App\Services\Qr;

use App\Models\Cita;
use App\Models\Pago;
use App\Models\QrCobro;
use App\Models\Usuario;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QrCobroService
{
    public function generar(Cita $cita, ?Usuario $usuario = null): array
    {
        $cita->loadMissing('pago', 'servicio');

        if ($cita->estado !== Cita::ESTADO_COMPLETADA) {
            throw ValidationException::withMessages([
                'cita' => 'Solo se puede generar un QR de cobro para una cita completada.',
            ]);
        }

        if (! $cita->pago) {
            throw ValidationException::withMessages([
                'cita' => 'La cita no tiene un pago registrado.',
            ]);
        }

        $existente = QrCobro::where('cita_id', $cita->id)
            ->where('estado', QrCobro::ESTADO_PENDIENTE)
            ->latest()
            ->first();

        if ($existente && $existente->estaVigente()) {
            return ['qr' => $existente, 'svg' => $this->svg($existente->payload)];
        }

        if ($existente && $existente->expira_en?->isPast()) {
            $existente->update(['estado' => QrCobro::ESTADO_EXPIRADO]);
        }

        $referencia = 'BLK-'.strtoupper(Str::random(12));
        $monto = (float) $cita->pago->monto;
        $payload = $this->payloadEmvco($referencia, $monto);

        $qr = QrCobro::create([
            'cita_id' => $cita->id,
            'pago_id' => $cita->pago->id,
            'referencia' => $referencia,
            'payload' => $payload,
            'monto' => $monto,
            'estado' => QrCobro::ESTADO_PENDIENTE,
            'expira_en' => now()->addMinutes((int) config('blackhound.qr_vigencia_minutos', 15)),
            'metadata' => [
                'generado_por' => $usuario?->id,
                'estandar' => 'EMVCo MPM (entorno de pruebas)',
            ],
        ]);

        return ['qr' => $qr, 'svg' => $this->svg($payload)];
    }

    public function confirmar(string $referencia, ?float $monto = null, ?string $idExterno = null): QrCobro
    {
        $qr = QrCobro::where('referencia', $referencia)->firstOrFail();

        if ($qr->estado === QrCobro::ESTADO_CONFIRMADO) {
            throw ValidationException::withMessages([
                'referencia' => 'El QR ya fue confirmado.',
            ]);
        }

        if ($qr->estado !== QrCobro::ESTADO_PENDIENTE) {
            throw ValidationException::withMessages([
                'referencia' => 'El QR no esta disponible para confirmacion.',
            ]);
        }

        if ($qr->expira_en && $qr->expira_en->isPast()) {
            $qr->update(['estado' => QrCobro::ESTADO_EXPIRADO]);

            throw ValidationException::withMessages([
                'referencia' => 'El QR expiro. Genera uno nuevo.',
            ]);
        }

        if ($monto !== null && abs((float) $qr->monto - $monto) > 0.01) {
            throw ValidationException::withMessages([
                'monto' => 'El monto confirmado no coincide con el QR.',
            ]);
        }

        return DB::transaction(function () use ($qr, $idExterno) {
            $metadata = $qr->metadata ?? [];
            $metadata['id_externo'] = $idExterno;
            $metadata['confirmado_en'] = now()->toIso8601String();

            $qr->update([
                'estado' => QrCobro::ESTADO_CONFIRMADO,
                'confirmado_en' => now(),
                'metadata' => $metadata,
            ]);

            if ($qr->pago && $qr->pago->estado !== Pago::ESTADO_PAGADO) {
                $qr->pago->update([
                    'estado' => Pago::ESTADO_PAGADO,
                    'metodo' => Pago::METODO_QR,
                    'pagado_en' => now(),
                ]);
            }

            return $qr->fresh(['cita', 'pago']);
        });
    }

    public function svg(string $payload): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(360, 2),
            new SvgImageBackEnd,
        );

        return (new Writer($renderer))->writeString($payload, 'UTF-8', ErrorCorrectionLevel::M());
    }

    /**
     * Payload EMVCo Merchant Presented Mode (MPM) para entorno de pruebas.
     */
    protected function payloadEmvco(string $referencia, float $monto): string
    {
        $payload = $this->tlv('00', '01')
            .$this->tlv('01', '12')
            .$this->tlv('26', $this->tlv('00', 'PE.BLACKHOUND').$this->tlv('01', $referencia))
            .$this->tlv('52', '7298')
            .$this->tlv('53', '604')
            .$this->tlv('54', number_format($monto, 2, '.', ''))
            .$this->tlv('58', 'PE')
            .$this->tlv('59', 'BLACKHOUND')
            .$this->tlv('60', 'LIMA')
            .$this->tlv('62', $this->tlv('05', $referencia))
            .'6304';

        return $payload.$this->crc16($payload);
    }

    protected function tlv(string $id, string $value): string
    {
        return $id.str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT).$value;
    }

    protected function crc16(string $data): string
    {
        $crc = 0xFFFF;

        for ($i = 0, $len = strlen($data); $i < $len; $i++) {
            $crc ^= ord($data[$i]) << 8;

            for ($bit = 0; $bit < 8; $bit++) {
                $crc = ($crc & 0x8000) !== 0
                    ? (($crc << 1) ^ 0x1021) & 0xFFFF
                    : ($crc << 1) & 0xFFFF;
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
