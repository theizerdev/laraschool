<?php

namespace App\Services;

use App\Models\Pago;
use Illuminate\Support\Facades\Http;

class ThermalPrinterService
{
    public static function listPrinters(): array
    {
        $api = config('printing.api_url', 'http://localhost:3001');
        try {
            $resp = Http::timeout(5)->get($api . '/api/printer/list');
            if ($resp->successful()) {
                return $resp->json()['printers'] ?? [];
            }
        } catch (\Exception $e) {
        }
        return [config('printing.default_printer')];
    }
    public static function isAvailable(string $printer = null): bool
    {
        $api = config('printing.api_url', 'http://localhost:3001');
        $printer = $printer ?: config('printing.default_printer');
        try {
            $resp = Http::timeout(5)->get($api . '/api/printer/status', [
                'name' => $printer
            ]);
            return $resp->successful() && ($resp->json()['available'] ?? false);
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function printPayment(Pago $pago, array $options = []): array
    {
        $api = config('printing.api_url', 'http://localhost:3001');
        $printer = $options['printer'] ?? config('printing.default_printer');
        $qrData = 'TX:' . ($pago->numero_completo ?? ($pago->serie . '-' . str_pad($pago->numero ?? 0, 8, '0', STR_PAD_LEFT)));
        $payload = [
            'printer' => $printer,
            'merchant' => [
                'name' => $pago->empresa->razon_social ?? 'Comercio',
                'branch' => $pago->sucursal->nombre ?? '',
                'address' => $pago->sucursal->direccion ?? ''
            ],
            'payment' => [
                'transaction' => $pago->numero_completo ?? ($pago->serie . '-' . str_pad($pago->numero ?? 0, 8, '0', STR_PAD_LEFT)),
                'amount_usd' => round($pago->total, 2),
                'amount_bs' => round($pago->total_bolivares ?? 0, 2),
                'method' => $pago->metodo_pago ?? 'N/A',
                'reference' => $pago->referencia ?? '',
                'date' => optional($pago->fecha)->format('d/m/Y'),
                'time' => optional($pago->created_at)->format('H:i')
            ],
            'qr' => [
                'data' => $qrData
            ],
            'options' => [
                'width' => $options['width'] ?? 80,
                'cut' => true
            ]
        ];

        try {
            $resp = Http::timeout(10)->post($api . '/api/printer/thermal', $payload);
            if ($resp->successful()) {
                return ['success' => true, 'message' => 'Impresión enviada'];
            }
            return ['success' => false, 'message' => $resp->json()['error'] ?? 'Error de impresión'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
