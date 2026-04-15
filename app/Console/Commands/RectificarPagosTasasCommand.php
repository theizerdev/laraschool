<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pago;
use App\Models\ExchangeRate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RectificarPagosTasasCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pagos:rectificar-tasas 
                            {--dry-run : Solo mostrar los cambios sin aplicarlos}
                            {--fecha-desde= : Fecha inicial (Y-m-d) para filtrar pagos}
                            {--fecha-hasta= : Fecha final (Y-m-d) para filtrar pagos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rectifica pagos que no tienen tasa de cambio ni total en Bolívares, usando el historial de tasas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando rectificación de tasas de cambio en pagos...');
        
        $dryRun = $this->option('dry-run');
        $fechaDesde = $this->option('fecha-desde');
        $fechaHasta = $this->option('fecha-hasta');
        
        // Construir query base
        $query = Pago::whereNull('tasa_cambio')
                    ->whereNull('total_bolivares')
                    ->where('total', '>', 0)
                    ->where('estado', 'aprobado');
        
        // Aplicar filtros de fecha si se proporcionan
        if ($fechaDesde) {
            $query->whereDate('fecha', '>=', $fechaDesde);
        }
        
        if ($fechaHasta) {
            $query->whereDate('fecha', '<=', $fechaHasta);
        }
        
        $pagosSinTasa = $query->get();
        
        if ($pagosSinTasa->isEmpty()) {
            $this->info('No se encontraron pagos sin tasa de cambio.');
            return 0;
        }
        
        $this->info("Se encontraron {$pagosSinTasa->count()} pagos sin tasa de cambio.");
        
        if ($dryRun) {
            $this->info('MODO SIMULACIÓN - No se aplicarán cambios');
        }
        
        $procesados = 0;
        $errores = 0;
        
        foreach ($pagosSinTasa as $pago) {
            try {
                $this->info("\nProcesando pago #{$pago->id} - Fecha: {$pago->fecha->format('d/m/Y')} - Total: \${$pago->total}");
                
                // Buscar la tasa de cambio para la fecha del pago
                $tasaCambio = $this->obtenerTasaParaFecha($pago->fecha);
                
                if (!$tasaCambio) {
                    $this->warn("  ⚠ No se encontró tasa de cambio para la fecha {$pago->fecha->format('d/m/Y')}");
                    $errores++;
                    continue;
                }
                
                $totalBolivares = $pago->total * $tasaCambio;
                
                $this->info("  ✓ Tasa encontrada: " . number_format($tasaCambio, 4) . " Bs/$");
                $this->info("  ✓ Total en Bs: " . number_format($totalBolivares, 2) . " Bs");
                
                if (!$dryRun) {
                    // Actualizar el pago
                    DB::transaction(function () use ($pago, $tasaCambio, $totalBolivares) {
                        $pago->updateQuietly([
                            'tasa_cambio' => $tasaCambio,
                            'total_bolivares' => $totalBolivares
                        ]);
                    });
                    $this->info("  ✓ Pago actualizado correctamente");
                } else {
                    $this->info("  → Simulación: Pago se actualizaría con tasa " . number_format($tasaCambio, 4));
                }
                
                $procesados++;
                
            } catch (\Exception $e) {
                $this->error("  ✗ Error procesando pago #{$pago->id}: " . $e->getMessage());
                $errores++;
            }
        }
        
        $this->info("\n" . str_repeat('=', 50));
        $this->info("RESUMEN DE LA OPERACIÓN");
        $this->info(str_repeat('=', 50));
        $this->info("Pagos procesados: {$procesados}");
        $this->info("Errores encontrados: {$errores}");
        $this->info("Total de pagos: " . $pagosSinTasa->count());
        
        if ($dryRun) {
            $this->warn("\n⚠ MODO SIMULACIÓN ACTIVADO - No se realizaron cambios en la base de datos");
        }
        
        return 0;
    }
    
    /**
     * Obtiene la tasa de cambio para una fecha específica
     * Si no existe para esa fecha, busca la más reciente anterior
     */
    private function obtenerTasaParaFecha(Carbon $fecha): ?float
    {
        // Primero intentar obtener la tasa exacta para la fecha
        $tasaExacta = ExchangeRate::whereDate('date', $fecha->format('Y-m-d'))
                                  ->whereNotNull('usd_rate')
                                  ->first();
        
        if ($tasaExacta) {
            return $tasaExacta->usd_rate;
        }
        
        // Si no existe, buscar la tasa más reciente anterior a esa fecha
        $tasaMasReciente = ExchangeRate::whereDate('date', '<=', $fecha->format('Y-m-d'))
                                       ->whereNotNull('usd_rate')
                                       ->orderBy('date', 'desc')
                                       ->first();
        
        if ($tasaMasReciente) {
            return $tasaMasReciente->usd_rate;
        }
        
        // Si no hay tasas anteriores, buscar la tasa más antigua disponible
        $tasaMasAntigua = ExchangeRate::whereNotNull('usd_rate')
                                     ->orderBy('date', 'asc')
                                     ->first();
        
        if ($tasaMasAntigua) {
            return $tasaMasAntigua->usd_rate;
        }
        
        // Si no hay ninguna tasa en el sistema, retornar null
        return null;
    }
}