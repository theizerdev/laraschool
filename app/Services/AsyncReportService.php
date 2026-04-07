<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Jobs\GenerateReportJob;
use App\Models\ReportExport;

class AsyncReportService
{
    /**
     * Iniciar la generación de un reporte asíncrono
     */
    public function startReportGeneration($type, $filters = [], $userId = null)
    {
        // Crear un registro del proceso de exportación
        $export = ReportExport::create([
            'user_id' => $userId,
            'type' => $type,
            'status' => 'pending',
            'filters' => $filters,
            'file_path' => null,
        ]);

        // Disparar el job para generar el reporte
        GenerateReportJob::dispatch($export->id, $type, $filters);

        return $export;
    }

    /**
     * Obtener el estado de un reporte en proceso
     */
    public function getReportStatus($exportId)
    {
        return ReportExport::find($exportId);
    }

    /**
     * Eliminar archivos de reportes antiguos
     */
    public function cleanupOldReports($days = 7)
    {
        $oldExports = ReportExport::where('created_at', '<', now()->subDays($days))->get();

        foreach ($oldExports as $export) {
            if ($export->file_path && Storage::exists($export->file_path)) {
                Storage::delete($export->file_path);
            }
            $export->delete();
        }
    }
}