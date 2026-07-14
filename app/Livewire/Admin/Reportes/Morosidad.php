<?php

namespace App\Livewire\Admin\Reportes;

use App\Traits\HasDynamicLayout;
use App\Traits\HasRegionalFormatting;
use Livewire\Component;
use App\Models\Student;
use App\Models\Matricula;
use App\Models\Pago;
use App\Models\EducationalLevel;
use App\Models\Programa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\DebtNotification;
use App\Services\WhatsAppService;
use App\Services\MorosidadCalculationService;
use Barryvdh\DomPDF\Facade\Pdf;

class Morosidad extends Component
{
    use HasDynamicLayout;
    use HasRegionalFormatting;

    public $nivelesEducativos;
    public $programas;
    public $nivel_educativo_id;
    public $programa_id;
    public $fecha_desde;
    public $fecha_hasta;
    public $morosos = [];
    public $solventes = [];
    public $totales = [];
    public $detalleDeuda = [];
    public $mostrarModal = false;
    public $estudianteSeleccionado = null;
    public $whatsappStatus = 'disconnected';
    public $sortBy  = 'estudiante_id';
    public $sortDirection  = 'asc';
    public $activeTab = 'morosos'; // 'morosos' o 'solventes'

    protected MorosidadCalculationService $morosidadCalculationService;

    public function __construct()
    {
        $this->morosidadCalculationService = app(MorosidadCalculationService::class);
    }

    public function mount()
    {
        $this->nivelesEducativos = EducationalLevel::all();
        $this->programas = collect(); // Inicialmente vacío
        $this->fecha_hasta = now()->format('Y-m-d');
        // Inicializar totales con valores por defecto
        $this->totales = [
            'total_estudiantes' => 0,
            'total_morosos' => 0,
            'porcentaje_morosidad' => 0
        ];

        // Verificar estado de WhatsApp
        $this->checkWhatsAppStatus();
    }

    public function updatedNivelEducativoId()
    {
        if ($this->nivel_educativo_id) {
            $this->programas = Programa::where('nivel_educativo_id', $this->nivel_educativo_id)->get();
        } else {
            $this->programas = collect();
        }

        $this->programa_id = '';
        $this->morosos = [];
        // Reinicializar totales cuando cambian los filtros
        $this->totales = [
            'total_estudiantes' => 0,
            'total_morosos' => 0,
            'porcentaje_morosidad' => 0
        ];
    }

    public function cargarReporte()
    {
        $query = Matricula::with(['estudiante', 'programa.nivelEducativo', 'turno', 'cronogramaPagos'])
            ->where('matriculas.estado', 'activo');

        if ($this->programa_id) {
            $query->where('matriculas.programa_id', $this->programa_id);
        } elseif ($this->nivel_educativo_id) {
            $query->join('programas', 'matriculas.programa_id', '=', 'programas.id')
                  ->where('programas.nivel_educativo_id', $this->nivel_educativo_id);
        }

        // Aseguramos que solo se traigan matrículas con estudiante asociado
        $query->whereHas('estudiante');

        $matriculas = $query->get();

        // Calcular morosidad para cada matrícula usando el servicio optimizado
        // Si no hay fecha_desde especificada, usar null para incluir todas las cuotas
        $fechaDesde = $this->fecha_desde ?: null;
        $fechaHasta = $this->fecha_hasta ?: null;

        $allData = $this->morosidadCalculationService->calculateMorosidadData(
            $matriculas,
            $fechaDesde,
            $fechaHasta
        );

        // Separar en morosos y solventes
        $morososData = collect($allData)->filter(function ($item) {
            return isset($item['estado']) && strtolower($item['estado']) === 'moroso';
        });

        $solventesData = collect($allData)->filter(function ($item) {
            return isset($item['estado']) && strtolower($item['estado']) !== 'moroso';
        });

        // Aplicar ordenamiento
        $this->morosos = $morososData->sortBy([[$this->sortBy, $this->sortDirection]])->values()->toArray();
        $this->solventes = $solventesData->sortBy([[$this->sortBy, $this->sortDirection]])->values()->toArray();

        // Calcular totales usando el servicio (para todos los estudiantes)
        $this->totales = $this->morosidadCalculationService->calculateTotals($allData);
    }

    public function mostrarDetalleDeuda($matriculaId)
    {
        // Obtener la matrícula con cronograma de pagos y pagos realizados
        $matricula = Matricula::with([
            'estudiante',
            'programa.nivelEducativo',
            'cronogramaPagos',
            'pagos.detalles.conceptoPago'
        ])->find($matriculaId);

        if (!$matricula) {
            return;
        }

        $this->estudianteSeleccionado = $matricula;

        // Filtrar cuotas según el rango de fechas
        $cuotas = $matricula->cronogramaPagos;

        if ($this->fecha_hasta) {
            $fechaCorte = \Carbon\Carbon::parse($this->fecha_hasta);
            $cuotas = $cuotas->filter(function ($cuota) use ($fechaCorte) {
                return $cuota->fecha_vencimiento->lte($fechaCorte);
            });
        }

        if ($this->fecha_desde) {
            $fechaDesde = \Carbon\Carbon::parse($this->fecha_desde);
            $cuotas = $cuotas->filter(function ($cuota) use ($fechaDesde) {
                return $cuota->fecha_vencimiento->gte($fechaDesde);
            });
        }

        // FILTRAR SOLO CUOTAS CON MOROSIDAD (no pagadas completamente)
        $cuotas = $cuotas->filter(function ($cuota) {
            $montoPagado = $cuota->monto_pagado ?? 0;
            return $montoPagado < $cuota->monto; // Solo incluir si no está completamente pagada
        });

        $this->detalleDeuda = $cuotas;
        $this->mostrarModal = true;

        // Emitir evento para mostrar la modal
        $this->dispatch('mostrarModal');
    }

    public function enviarNotificacionDeuda()
    {
        if (!$this->estudianteSeleccionado) {
            session()->flash('error', 'No se ha seleccionado un estudiante.');
            return;
        }

        $estudiante = $this->estudianteSeleccionado->estudiante; // Usando la relación correcta

        // Verificar si el estudiante es mayor de edad
        $esMayorDeEdad = $estudiante->fecha_nacimiento &&
                         $estudiante->fecha_nacimiento->age >= 18;

        $correoDestino = null;
        $nombreDestino = null;

        if ($esMayorDeEdad && $estudiante->correo_electronico) {
            // Enviar al correo del estudiante si es mayor de edad
            $correoDestino = $estudiante->correo_electronico;
            $nombreDestino = $estudiante->nombres . ' ' . $estudiante->apellidos;
        } elseif (!$esMayorDeEdad && $estudiante->representante_correo) {
            // Enviar al correo del representante si es menor de edad
            $correoDestino = $estudiante->representante_correo;
            $nombreDestino = $estudiante->representante_nombres . ' ' . $estudiante->representante_apellidos;
        }

        // Agregar información de depuración
        Log::info('Verificación de correo para notificación', [
            'es_mayor_de_edad' => $esMayorDeEdad,
            'estudiante_email' => $estudiante->correo_electronico,
            'representante_email' => $estudiante->representante_correo,
            'correo_destino' => $correoDestino
        ]);

        if (!$correoDestino) {
            // Mensaje más detallado para diagnosticar el problema
            if (!$esMayorDeEdad && !$estudiante->representante_correo) {
                session()->flash('error', 'No se encontró un correo de representante para enviar la notificación. Verifique que el estudiante tenga un correo de representante registrado.');
            } else {
                session()->flash('error', 'No se encontró un correo válido para enviar la notificación.');
            }
            return;
        }

        try {
            // Preparar datos para el correo
            $pendingAmount = ($this->estudianteSeleccionado->costo ?? 0) - $this->estudianteSeleccionado->pagos->sum('total');

            // Enviar correo real
            Mail::to($correoDestino)->send(new DebtNotification($estudiante, $this->detalleDeuda, $pendingAmount));

            Log::info('Notificación de deuda enviada', [
                'destinatario' => $correoDestino,
                'estudiante' => $estudiante->nombres . ' ' . $estudiante->apellidos,
                'saldo_pendiente' => $pendingAmount
            ]);

            session()->flash('message', 'Notificación enviada correctamente a ' . $nombreDestino . ' (' . $correoDestino . ')');
        } catch (\Exception $e) {
            Log::error('Error al enviar notificación de deuda', [
                'error' => $e->getMessage(),
                'estudiante_id' => $estudiante->id
            ]);

            session()->flash('error', 'Error al enviar la notificación. Por favor, inténtelo de nuevo.');
        }
    }

    public function cerrarModal()
    {
        $this->mostrarModal = false;
        $this->detalleDeuda = [];
        $this->estudianteSeleccionado = null;
    }

    public function checkWhatsAppStatus()
    {
        try {
            $whatsappService = app(WhatsAppService::class);
            $status = $whatsappService->getStatus();

            $this->whatsappStatus = $status['connectionState'] ?? 'disconnected';
        } catch (\Exception $e) {
            $this->whatsappStatus = 'disconnected';
        }
    }

    public function enviarWhatsAppMorosidad()
    {
        if (!$this->estudianteSeleccionado) {
            session()->flash('error', 'No se ha seleccionado un estudiante.');
            return;
        }

        $estudiante = $this->estudianteSeleccionado->estudiante; // Usando la relación correcta


        $esMayorDeEdad = $estudiante->fecha_nacimiento && $estudiante->fecha_nacimiento->age >= 18;

        $telefono = null;
        $nombreDestino = null;

        if ($esMayorDeEdad && $estudiante->phone) {
            $telefono = $estudiante->phone;
            $nombreDestino = $estudiante->nombres . ' ' . $estudiante->apellidos;
            } elseif (!$esMayorDeEdad && $estudiante->representante_telefonos) {
                // Decodificar JSON y tomar el primer teléfono
                $telefonos = explode(',', $estudiante->representante_telefonos);
                $telefono = trim($telefonos[0] ?? '');


            $nombreDestino = $estudiante->representante_nombres . ' ' . $estudiante->representante_apellidos;
            //dd(!$telefono);
        }
        else
        {
            $telefono = $estudiante->telefono;
            $nombreDestino = $estudiante->nombres . ' ' . $estudiante->apellidos;
        }
        if (!$telefono) {
            session()->flash('error', 'No se encontró un teléfono válido para enviar el mensaje.');
            return;
        }

        // Generar PDF del detalle de deuda
        try {
            $pdfPath = $this->generarPDFDeudaIndividual($estudiante, $this->estudianteSeleccionado);

            if (!$pdfPath) {
                session()->flash('error', 'Error al generar el documento PDF.');
                return;
            }

            // Crear mensaje de texto resumido
            $saldoPendiente = ($this->estudianteSeleccionado->costo ?? 0) - $this->estudianteSeleccionado->pagos->sum('total');
            $mensajeResumen = $this->generarMensajeResumenMorosidad($estudiante, $saldoPendiente, $esMayorDeEdad);

            // Formatear teléfono según el país de la empresa
            $telefonoFormateado = $this->formatPhoneNumber($telefono);

            try {
                $whatsappService = app(WhatsAppService::class);

                // Enviar mensaje de texto primero
                $resultText = $whatsappService->sendMessage($telefonoFormateado, $mensajeResumen,true);;

                // Luego enviar el PDF como documento
                if ($resultText && ($resultText['success'] ?? false)) {
                    $resultPDF = $whatsappService->sendDocument(
                        $telefonoFormateado,
                        $pdfPath,
                        'Detalle de Deuda - U.E VARGAS II'
                    );

                    if ($resultPDF && ($resultPDF['success'] ?? false)) {
                        session()->flash('success', 'Notificación enviada correctamente a ' . $nombreDestino . ' (Mensaje + PDF adjunto)');
                    } else {
                        session()->flash('warning', 'Mensaje enviado pero hubo un problema al adjuntar el PDF.');
                    }
                } else {
                    session()->flash('error', 'Error al enviar el mensaje de WhatsApp.');
                }
            } catch (\Exception $e) {
                session()->flash('error', 'Error al enviar mensaje: ' . $e->getMessage());
            }

            // Eliminar el archivo PDF temporal después de enviarlo
            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }

        } catch (\Exception $e) {
            \Log::error('Error enviando WhatsApp con PDF', [
                'error' => $e->getMessage(),
                'estudiante_id' => $estudiante->id
            ]);
            session()->flash('error', 'Error al procesar la notificación: ' . $e->getMessage());
        }
    }

    private function generarMensajeMorosidad($estudiante, $saldoPendiente, $esMayorDeEdad)
    {
        $nombreEstudiante = $estudiante->nombres . ' ' . $estudiante->apellidos;
        $saldoFormateado = '$' . number_format($saldoPendiente, 2, ',', '.');

        if ($esMayorDeEdad) {
            $mensaje = " *Recordatorio de Pago - U.E JOSE MARIA VARGAS*\n\n";
            $mensaje .= "Estimado/a {$nombreEstudiante},\n\n";
            $mensaje .= "Le recordamos que tiene un saldo pendiente de *{$saldoFormateado}* en su matrícula.\n\n";
        } else {
            $representante = $estudiante->representante_nombres . ' ' . $estudiante->representante_apellidos;
            $mensaje = "🔔 *Recordatorio de Pago - U.E JOSE MARIA VARGAS*\n\n";
            $mensaje .= "Estimado/a {$representante},\n\n";
            $mensaje .= "Le recordamos que el estudiante *{$nombreEstudiante}* tiene un saldo pendiente de *{$saldoFormateado}* en su matrícula.\n\n";
        }

        $mensaje .= "📅 *Detalle de cuotas pendientes:*\n";
        foreach ($this->detalleDeuda as $cuota) {
            $fechaVencimiento = \Carbon\Carbon::parse($cuota->fecha_vencimiento)->format('d/m/Y');
            $montoCuota = '$' . number_format($cuota->monto, 2, ',', '.');
            $montoPagado = $cuota->monto_pagado ?? 0;

            // Si hay abono parcial, mostrarlo
            if ($montoPagado > 0) {
                $montoAbonado = '$' . number_format($montoPagado, 2, ',', '.');
                $montoRestante = '$' . number_format($cuota->monto - $montoPagado, 2, ',', '.');
                $mensaje .= "• {$cuota->descripcion} (Vence: {$fechaVencimiento})\n";
                $mensaje .= "   Monto: {$montoCuota} | Abonado: {$montoAbonado} | Restante: {$montoRestante}\n";
            } else {
                $mensaje .= "• {$cuota->descripcion}: {$montoCuota} (Vence: {$fechaVencimiento})\n";
            }
        }

        $mensaje .= "\n💳 Para realizar su pago, puede acercarse a nuestras oficinas o contactarnos.\n\n";
        $mensaje .= "Gracias por su atención.\n\n";
        $mensaje .= "*U.E JOSE MARIA VARGAS*";

        return $mensaje;
    }

    /**
     * Genera un mensaje resumen corto para WhatsApp
     */
    private function generarMensajeResumenMorosidad($estudiante, $saldoPendiente, $esMayorDeEdad)
    {
        $nombreEstudiante = $estudiante->nombres . ' ' . $estudiante->apellidos;
        $saldoFormateado = '$' . number_format($saldoPendiente, 2, ',', '.');

        if ($esMayorDeEdad) {
            $mensaje = "🔔 *Recordatorio de Pago - U.E JOSE MARIA VARGAS*\n\n";
            $mensaje .= "Estimado/a {$nombreEstudiante},\n\n";
            $mensaje .= "Tiene un saldo pendiente de *{$saldoFormateado}*\n\n";
        } else {
            $representante = $estudiante->representante_nombres . ' ' . $estudiante->representante_apellidos;
            $mensaje = " *Recordatorio de Pago - U.E JOSE MARIA VARGAS*\n\n";
            $mensaje .= "Estimado/a {$representante},\n\n";
            $mensaje .= "El estudiante *{$nombreEstudiante}* tiene saldo pendiente de *{$saldoFormateado}*\n\n";
        }

        $mensaje .= "📄 Adjuntamos documento PDF con el detalle completo de sus cuotas pendientes.\n\n";
        $mensaje .= "Por favor, revise el documento adjunto para ver:\n";
        $mensaje .= "✅ Detalle de cada cuota\n";
        $mensaje .= "✅ Montos abonados\n";
        $mensaje .= "✅ Saldo restante por pagar\n\n";
        $mensaje .= "💳 Regularice su situación financiera a la brevedad.\n\n";
        $mensaje .= "*U.E JOSE MARIA VARGAS*";

        return $mensaje;
    }

    /**
     * Genera PDF individual del detalle de deuda
     */
    private function generarPDFDeudaIndividual($estudiante, $matricula)
    {
        try {
            // Calcular totales
            $costoTotal = $this->detalleDeuda->sum('monto');
            $totalPagado = $this->detalleDeuda->sum('monto_pagado');
            $saldoPendiente = $costoTotal - $totalPagado;

            // Obtener tasa de cambio actual (si existe)
            $tasaCambio = \App\Models\ExchangeRate::getLatestRate('USD') ?? 565; // Valor por defecto si no hay tasa activa

            // Determinar si es mayor de edad
            $esMayorDeEdad = $estudiante->fecha_nacimiento &&
                             $estudiante->fecha_nacimiento->age >= 18;

            $data = [
                'estudiante' => $estudiante,
                'matricula' => $matricula,
                'cuotas' => $this->detalleDeuda,
                'costoTotal' => $costoTotal,
                'totalPagado' => $totalPagado,
                'saldoPendiente' => $saldoPendiente,
                'tasaCambio' => $tasaCambio,
                'esMayorDeEdad' => $esMayorDeEdad
            ];

            // Generar PDF
            $pdf = Pdf::loadView('admin.reportes.detalle-deuda-individual', $data);

            // Guardar en storage temporal
            $filename = 'deuda_' . $estudiante->id . '_' . time() . '.pdf';
            $path = storage_path('app/temp/' . $filename);

            // Crear directorio si no existe
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            // Guardar archivo
            file_put_contents($path, $pdf->output());

            return $path;

        } catch (\Exception $e) {
            \Log::error('Error generando PDF de deuda individual', [
                'error' => $e->getMessage(),
                'estudiante_id' => $estudiante->id
            ]);
            return null;
        }
    }

    private function formatPhoneNumber($number)
    {
        // Obtener el código del país de la empresa
        $empresa = \DB::table('empresas')->where('id', 1)->first();
        $pais = $empresa ? \DB::table('pais')->where('id', $empresa->pais_id)->first() : null;
        $codigoPais = $pais ? $pais->codigo_telefonico : '58'; // Default Venezuela

        // Limpiar número
        $cleaned = preg_replace('/[^0-9]/', '', $number);

        // Si ya tiene código de país, devolverlo
        if (strlen($cleaned) > 10 && str_starts_with($cleaned, $codigoPais)) {
            return $cleaned;
        }

        // Quitar el 0 inicial si existe
        if (str_starts_with($cleaned, '0')) {
            $cleaned = substr($cleaned, 1);
        }

        // Agregar código de país
        return $codigoPais . $cleaned;
    }

       public function exportarExcel()
    {
        if (count($this->morosos) == 0) {
            session()->flash('error', 'No hay datos de morosidad para exportar.');
            return;
        }

        try {
            // 1. Separar los datos en Morosos y Solventes
            // Ajusta el valor 'Moroso' y 'Solvente' según el nombre exacto que genere tu calculateEstado
            $datosMorosos = collect($this->morosos)->filter(function ($item) {
                return isset($item['estado']) && strtolower($item['estado']) === 'moroso';
            });

            $datosSolventes = collect($this->morosos)->filter(function ($item) {
                return isset($item['estado']) && strtolower($item['estado']) !== 'moroso';
            });

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

            // Definir estilos reutilizables
            $headerStyleMorosos = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => 'DC3545']], // Rojo
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
            ];

            $headerStyleSolventes = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => '28A745']], // Verde
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
            ];

            // 2. Generar Pestaña de MOROSOS
            $sheetMorosos = $spreadsheet->getActiveSheet();
            $sheetMorosos->setTitle('Morosos');
            $this->generarContenidoHoja($sheetMorosos, $datosMorosos, 'REPORTE DE MOROSIDAD', $headerStyleMorosos, 'DC3545');

            // 3. Generar Pestaña de SOLVENTES
            $sheetSolventes = $spreadsheet->createSheet();
            $sheetSolventes->setTitle('Solventes');
            $this->generarContenidoHoja($sheetSolventes, $datosSolventes, 'REPORTE DE SOLVENTES', $headerStyleSolventes, '28A745');

            $filename = 'reporte_morosidad_' . now()->format('Y-m-d') . '.xlsx';

            session()->flash('success', 'Archivo Excel generado correctamente con pestañas separadas.');

            return new \Symfony\Component\HttpFoundation\StreamedResponse(
                function () use ($spreadsheet) {
                    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                    $writer->save('php://output');
                },
                200,
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Content-Disposition' => 'attachment; filename="' . urlencode($filename) . '"',
                    'Cache-Control' => 'max-age=0',
                ]
            );
        } catch (\Exception $e) {
            \Log::error('Error exportando Excel morosidad: ' . $e->getMessage());
            session()->flash('error', 'Error al generar el archivo Excel: ' . $e->getMessage());
            return;
        }
    }

    /**
     * Función auxiliar para no repetir el código de dibujado de tablas en Excel
     */
    private function generarContenidoHoja($sheet, $datos, $titulo, $headerStyle, $colorEstado)
    {
        // Encabezado principal
        $sheet->setCellValue('A1', $titulo);
        $sheet->mergeCells('A1:K1'); // Ajustado a K por las 11 columnas
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => $colorEstado]],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => 'F8F9FA']]
        ]);

        // Información del reporte
        $sheet->setCellValue('A3', 'Fecha de generación:');
        $sheet->setCellValue('B3', now()->format('d/m/Y H:i:s'));
        $sheet->setCellValue('A4', 'Rango de fechas:');
        $rangoFechas = ($this->fecha_desde ? \Carbon\Carbon::parse($this->fecha_desde)->format('d/m/Y') : 'Inicio') .
                      ' - ' .
                      ($this->fecha_hasta ? \Carbon\Carbon::parse($this->fecha_hasta)->format('d/m/Y') : 'Hoy');
        $sheet->setCellValue('B4', $rangoFechas);
        $sheet->setCellValue('A5', 'Total registros:');
        $sheet->setCellValue('B5', $datos->count());

        $sheet->getStyle('A3:A5')->getFont()->setBold(true);

        // Encabezados de la tabla
        $headers = ['Estudiante', 'Documento', 'Programa', 'Nivel', 'Turno', 'Estado', 'Cantidad de Cuotas', 'Costo Total (Rango)', 'Total Pagado (Rango)', 'Saldo Pendiente (Rango)', '% Pagado (Rango)'];

        foreach ($headers as $index => $header) {
            $column = chr(65 + $index);
            $sheet->setCellValue($column . '7', $header);
        }

        $sheet->getStyle('A7:K7')->applyFromArray($headerStyle);

        // Datos
        $row = 8;
        foreach ($datos as $moroso) {
            $sheet->setCellValue('A' . $row, $moroso['estudiante_nombre']);
            $sheet->setCellValue('B' . $row, $moroso['matricula']->estudiante->documento_identidad ?? 'N/A');
            $sheet->setCellValue('C' . $row, $moroso['programa_nombre']);
            $sheet->setCellValue('D' . $row, $moroso['nivel_nombre']);
            $sheet->setCellValue('E' . $row, $moroso['turno_nombre']);
            $sheet->setCellValue('F' . $row, $moroso['estado']);
            $sheet->setCellValue('G' . $row, $moroso['cantidad_cuotas']);
            $sheet->setCellValue('H' . $row, $moroso['costo_rango']);
            $sheet->setCellValue('I' . $row, $moroso['pagado_rango']);
            $sheet->setCellValue('J' . $row, $moroso['saldo_pendiente_rango']);
            $sheet->setCellValue('K' . $row, $moroso['porcentaje_pagado_rango'] / 100);
            $row++;
        }

        // Formato de la tabla (solo si hay datos)
        if ($row > 8) {
            $rangeData = 'A8:K' . ($row - 1);
            $sheet->getStyle($rangeData)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $sheet->getStyle('H8:J' . ($row - 1))->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('K8:K' . ($row - 1))->getNumberFormat()->setFormatCode('0.00%');
        }

        // Configuración de columnas
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(18);
        $sheet->getColumnDimension('I')->setWidth(18);
        $sheet->getColumnDimension('J')->setWidth(18);
        $sheet->getColumnDimension('K')->setWidth(15);
    }

    public function exportarPDF()
    {
        if (count($this->morosos) == 0) {
            session()->flash('error', 'No hay datos de morosidad para exportar.');
            return;
        }

        try {
            $rangoFechas = ($this->fecha_desde ? \Carbon\Carbon::parse($this->fecha_desde)->format('d/m/Y') : 'Inicio') .
                          ' - ' .
                          ($this->fecha_hasta ? \Carbon\Carbon::parse($this->fecha_hasta)->format('d/m/Y') : 'Hoy');

            $data = [
                'morosos' => $this->morosos,
                'totales' => $this->totales,
                'rango_fechas' => $rangoFechas,
                'fecha_generacion' => now()->format('d/m/Y H:i:s')
            ];

            $pdf = Pdf::loadView('admin.reportes.morosidad-pdf', $data);
            $filename = 'reporte_morosidad_' . now()->format('Y-m-d') . '.pdf';

            session()->flash('success', 'Archivo PDF generado correctamente.');
            return $pdf->download($filename);
        } catch (\Exception $e) {
            \Log::error('Error exportando PDF morosidad: ' . $e->getMessage());
            session()->flash('error', 'Error al generar el archivo PDF: ' . $e->getMessage());
            return;
        }
    }

    public function enviarNotificaciones()
    {
        if (count($this->morosos) == 0) {
            session()->flash('error', 'No hay estudiantes morosos para notificar.');
            return;
        }

        $notificacionesEnviadas = 0;
        $errores = [];

        foreach ($this->morosos as $moroso) {
            $estudiante = $moroso['matricula']->estudiante; // Usando la relación correcta según la convención

            // Verificar si el estudiante es mayor de edad
            $esMayorDeEdad = $estudiante->fecha_nacimiento &&
                             $estudiante->fecha_nacimiento->age >= 18;

            $correoDestino = null;
            $nombreDestino = null;

            if ($esMayorDeEdad && $estudiante->correo_electronico) {
                // Enviar al correo del estudiante si es mayor de edad
                $correoDestino = $estudiante->correo_electronico;
                $nombreDestino = $estudiante->nombres . ' ' . $estudiante->apellidos;
            } elseif (!$esMayorDeEdad && $estudiante->representante_correo) {
                // Enviar al correo del representante si es menor de edad
                $correoDestino = $estudiante->representante_correo;
                $nombreDestino = $estudiante->representante_nombres . ' ' . $estudiante->representante_apellidos;
            }

            if ($correoDestino) {
                try {
                    // Preparar datos para el correo
                    $pendingAmount = $moroso['saldo_pendiente'];

                    // Obtener cronograma de pagos pendientes
                    $cronogramaPendiente = $moroso['matricula']->cronogramaPagos
                        ->where('estado', 'pendiente');

                    // Enviar correo real
                    \Mail::to($correoDestino)->send(new \App\Mail\DebtNotification($estudiante, $cronogramaPendiente, $pendingAmount));
                    $notificacionesEnviadas++;

                    \Log::info('Notificación de morosidad enviada', [
                        'destinatario' => $correoDestino,
                        'estudiante' => $estudiante->nombres . ' ' . $estudiante->apellidos,
                        'saldo_pendiente' => $pendingAmount
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Error enviando notificación de morosidad', [
                        'error' => $e->getMessage(),
                        'estudiante' => $estudiante->nombres . ' ' . $estudiante->apellidos
                    ]);
                    $errores[] = $estudiante->nombres . ' ' . $estudiante->apellidos;
                }
            } else {
                $errores[] = $estudiante->nombres . ' ' . $estudiante->apellidos . ' (sin correo válido)';
            }
        }

        if ($notificacionesEnviadas > 0) {
            session()->flash('success', "Se enviaron {$notificacionesEnviadas} notificaciones correctamente (mayores de edad al estudiante, menores al representante).");
        }

        if (count($errores) > 0) {
            session()->flash('error', 'No se pueden notificar a: ' . implode(', ', array_slice($errores, 0, 3)) . (count($errores) > 3 ? ' y ' . (count($errores) - 3) . ' más.' : '.'));
        }
    }

    public function render()
    {
        return view('livewire.admin.reportes.morosidad')->layout($this->getLayout());
    }
}
