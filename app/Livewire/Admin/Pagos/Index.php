<?php

namespace App\Livewire\Admin\Pagos;
use App\Traits\HasDynamicLayout;
use App\Traits\HasRegionalFormatting;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Pago;
use App\Models\ExchangeRate;
use App\Traits\Exportable;
use Codedge\Fpdf\Fpdf\Fpdf;

class Index extends Component
{
    use WithPagination, Exportable, HasDynamicLayout, HasRegionalFormatting;

    public $showPreview = false;
    public $previewPagoId;

    public $search = '';
    public $status = '';
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'sortBy' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
        'perPage' => ['except' => 10]
    ];

    public function getStatsProperty()
    {
        // Para usuarios no Super Administrador, usar withoutGlobalScope y aplicar manualmente
        if (auth()->check() && !auth()->user()->hasRole('Super Administrador')) {
            $baseQuery = Pago::withoutGlobalScope('multitenancy')
                ->where(function($query) {
                    if (auth()->user()->empresa_id) {
                        $query->where('pagos.empresa_id', auth()->user()->empresa_id);
                    }
                    if (auth()->user()->sucursal_id) {
                        $query->where('pagos.sucursal_id', auth()->user()->sucursal_id);
                    }
                })
                ->whereHas('matricula', function($q) {
                    $q->whereHas('student');
                });
        } else {
            $baseQuery = Pago::whereHas('matricula', function($q) {
                $q->whereHas('student');
            });
        }

        return [
            'total' => (clone $baseQuery)->count(),
            'aprobados' => (clone $baseQuery)->where('estado', 'aprobado')->count(),
            'pendientes' => (clone $baseQuery)->where('estado', 'pendiente')->count(),
            'ingresos_totales' => (clone $baseQuery)->where('estado', 'aprobado')->sum('total') ?: 0
        ];
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatus()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }

        $this->sortBy = $field;
        $this->resetPage();
    }

    public function delete(Pago $pago)
    {
        // Verificar permiso para eliminar pagos
        if (!auth()->user()->can('delete pagos')) {
            session()->flash('error', 'No tienes permiso para eliminar pagos.');
            return;
        }

        try {
            $pago->delete();
            session()->flash('message', 'Pago eliminado correctamente.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al eliminar el pago: ' . $e->getMessage());
        }

        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->status = '';
        $this->sortBy = 'created_at';
        $this->sortDirection = 'desc';
        $this->perPage = 10;
        $this->resetPage();
    }

    public function toggleStatus($pagoId)
    {
        if (!auth()->user()->can('edit pagos')) {
            session()->flash('error', 'No tienes permiso para editar pagos.');
            return;
        }

        $pago = Pago::find($pagoId);
        if ($pago) {
            $pago->estado = $pago->estado === 'aprobado' ? 'pendiente' : 'aprobado';
            $pago->save();
        }
    }

    public function getExportQuery()
    {
        return $this->getQuery();
    }

    public function getExportHeaders()
    {
        return [
            'Documento', 'Estudiante', 'DNI', 'Total', 'Fecha', 'Estado', 'Método Pago'
        ];
    }

    public function formatExportRow($pago)
    {
        $studentName = '';
        $studentDocumento = '';

        if ($pago->matricula && $pago->matricula->student) {
            $studentName = ($pago->matricula->student->nombres ?? '') . ' ' . ($pago->matricula->student->apellidos ?? '');
            $studentDocumento = $pago->matricula->student->documento_identidad ?? '';
        }

        return [
            $pago->numero_completo,
            $studentName,
            $studentDocumento,
            $this->format_money($pago->total),
            $this->format_date($pago->fecha),
            ucfirst($pago->estado),
            $pago->metodo_pago ?? ''
        ];
    }

    private function getQuery()
    {
        // Para usuarios no Super Administrador, usar withoutGlobalScope y aplicar manualmente solo a pagos
        if (auth()->check() && !auth()->user()->hasRole('Super Administrador')) {
            return Pago::withoutGlobalScope('multitenancy')
                ->with(['matricula.student', 'detalles.conceptoPago', 'user', 'serieModel'])
                ->where(function($query) {
                    // Aplicar scope manualmente solo a pagos
                    if (auth()->user()->empresa_id) {
                        $query->where('pagos.empresa_id', auth()->user()->empresa_id);
                    }
                    if (auth()->user()->sucursal_id) {
                        $query->where('pagos.sucursal_id', auth()->user()->sucursal_id);
                    }
                })
                ->whereHas('matricula', function($q) {
                    $q->whereHas('student');
                })
                ->when($this->search, function ($query) {
                    $query->where(function($q) {
                        $q->whereHas('matricula.student', function ($subQuery) {
                            $subQuery->where('nombres', 'like', '%' . $this->search . '%')
                                ->orWhere('apellidos', 'like', '%' . $this->search . '%')
                                ->orWhere('documento_identidad', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('detalles.conceptoPago', function($subQuery) {
                            $subQuery->where('nombre', 'like', '%' . $this->search . '%');
                        })
                        ->orWhere('referencia', 'like', '%' . $this->search . '%')
                        ->orWhere('serie', 'like', '%' . $this->search . '%')
                        ->orWhere('numero', 'like', '%' . $this->search . '%');
                    });
                })
                ->when($this->status !== '', function ($query) {
                    $query->where('estado', $this->status);
                })
                ->orderBy($this->sortBy, $this->sortDirection);
        }

        // Para Super Administrador, usar el scope normal
        return Pago::with(['matricula.student', 'detalles.conceptoPago', 'user', 'serieModel'])
                    ->whereHas('matricula', function($q) {
                        $q->whereHas('student');
                    })
                    ->when($this->search, function ($query) {
                        $query->where(function($q) {
                            $q->whereHas('matricula.student', function ($subQuery) {
                                $subQuery->where('nombres', 'like', '%' . $this->search . '%')
                                    ->orWhere('apellidos', 'like', '%' . $this->search . '%')
                                    ->orWhere('documento_identidad', 'like', '%' . $this->search . '%');
                            })
                            ->orWhereHas('detalles.conceptoPago', function($subQuery) {
                                $subQuery->where('nombre', 'like', '%' . $this->search . '%');
                            })
                            ->orWhere('referencia', 'like', '%' . $this->search . '%')
                            ->orWhere('serie', 'like', '%' . $this->search . '%')
                            ->orWhere('numero', 'like', '%' . $this->search . '%');
                        });
                    })
                    ->when($this->status !== '', function ($query) {
                        $query->where('estado', $this->status);
                    })
                    ->orderBy($this->sortBy, $this->sortDirection);
    }

    public function render()
    {
        $pagos = $this->getQuery()->paginate($this->perPage);

        // Debug temporal para verificar datos
        \Log::info('=== RENDER DE PAGOS COMPONENT ===', [
            'user_id' => auth()->id(),
            'user_role' => auth()->user()->roles->first()->name ?? 'no role',
            'is_super_admin' => auth()->user()->hasRole('Super Administrador'),
            'empresa_id' => auth()->user()->empresa_id,
            'sucursal_id' => auth()->user()->sucursal_id,
            'pagos_count' => $pagos->count(),
            'pagos_total' => $pagos->total(),
            'per_page' => $this->perPage,
            'search' => $this->search,
            'status' => $this->status,
            'sql' => $this->getQuery()->toSql(),
            'bindings' => $this->getQuery()->getBindings()
        ]);

        return view('livewire.admin.pagos.index', compact('pagos'))
            ->layout($this->getLayout());
    }

    public function printReceipt(Pago $pago)
    {
        $this->previewPagoId = $pago->id;
        $this->showPreview = true;
    }

    public function downloadReceipt(Pago $pago)
    {
        $pdf = new Fpdf('P', 'mm', 'Letter');
        $pdf->AddPage();

        // Configurar fuentes
        $pdf->SetFont('Arial', 'B', 16);

        // Mitad de la página (para el recibo original y copia)
        $pageHeight = 279.4; // Altura de carta en mm
        $halfPage = $pageHeight / 2;

        // Generar recibo original en la mitad superior
        $this->generateReceiptContent($pdf, $pago, 'ORIGINAL', 5);

        // Generar copia en la mitad inferior
        $this->generateReceiptContent($pdf, $pago, 'COPIA', $halfPage + 7);

        // Mostrar PDF en el navegador en lugar de descargarlo
        return response($pdf->Output('S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="recibo_pago_' . $pago->numero_completo . '.pdf"'
        ]);
    }

    public function generateReceiptContent(Fpdf $pdf, Pago $pago, $tipo, $yPosition)
    {
        // Establecer posición Y inicial
        $pdf->SetY($yPosition);

        // Encabezado con tipo de recibo
        $pdf->SetFont('Arial', 'B', 16);
        //$pdf->Cell(0, 8, 'RECIBO DE PAGO - ' . $tipo, 0, 1, 'C');

        // Línea divisoria
        //$pdf->Line(10, $pdf->GetY() + 2, 200, $pdf->GetY() + 2);
        $pdf->Ln(22);

        // Obtener tasa de cambio
        $exchangeRate = ExchangeRate::whereDate('created_at', $pago->created_at)->first();
        //dd($exchangeRate);

        // Información del pago (alineada a la izquierda)
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(30, 5, 'FACTURA NRO:', 0, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        // Extraer solo el número después del guión
        $numeroRecibo = explode('-', $pago->numero_completo);
        $numeroMostrar = isset($numeroRecibo[1]) ? $numeroRecibo[1] : $pago->numero_completo;
        $pdf->Cell(0, 5, $numeroMostrar, 0, 1, 'L');

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(30, 5, 'Fecha de pago:', 0, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(0, 5, $pago->fecha->format('d/m/Y'), 0, 1, 'L');

    


        // Información del estudiante
        $student = $pago->matricula->student;
       
        $fechaNacimiento = \Carbon\Carbon::parse($student->fecha_nacimiento);
        $esMenorEdad = $fechaNacimiento->age < 18;
    
        $pdf->SetFont('Arial', 'B', 8);
        if ($esMenorEdad != true) {
             $pdf->Cell(30, 5, 'Estudiante:', 0, 0, 'L');
             $pdf->SetFont('Arial', '', 8);
             $pdf->Cell(0, 5, substr(utf8_decode($student->nombres . ' ' . $student->apellidos), 0, 45), 0, 1, 'L');
        } else {
             $pdf->Cell(30, 5, 'Estudiante:', 0, 0, 'L');
             $pdf->SetFont('Arial', '', 8);
             $pdf->Cell(0, 5, utf8_decode($student->nombres . ' ' . $student->apellidos.' ('.$student->grado.' - '.$student->seccion.') Representante: '.$student->representante_nombres.' '.$student->representante_apellidos), 0, 1, 'L');
        }
       
        


        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(30, 5, 'Fecha de emision:', 0, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(0, 5, $pago->created_at->format('d/m/Y'), 0, 1, 'L');

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(30, 5, utf8_decode('Método de pago:'), 0, 0, 'L');
        
        // Para pagos mixtos, mostrar el método con los detalles en la misma línea
         if (strtolower($pago->metodo_pago) === 'pago mixto' && !empty($pago->detalles_pago_mixto)) {
            $pdf->SetFont('Arial', 'B', 8);
            $detalles = [];
            foreach ($pago->detalles_pago_mixto as $detalleMixto) {
                $metodo = ucfirst(str_replace('_', ' ', $detalleMixto['metodo'] ?? ''));
                $monto = $detalleMixto['monto'] ?? 0;
                $referencia = $detalleMixto['referencia'] ?? '';
                $detalles[] = "$metodo: " . number_format($monto, 2, ',', '.') . ($referencia ? " - Ref: $referencia" : "");
            }
            $pdf->Cell(0, 5, strtoupper($pago->metodo_pago) . ' (' . implode(' ', $detalles) . ')', 0, 1, 'L');
        } else {
            // Para métodos de pago normales, mostrar referencia en la misma línea si existe
            $metodoPagoTexto = strtoupper($pago->metodo_pago);
            if (in_array($pago->metodo_pago, ['transferencia', 'pago movil', 'punto de venta']) && !empty($pago->referencia)) {
                $metodoPagoTexto .= ' - Ref: ' . $pago->referencia;
            }
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(0, 5, $metodoPagoTexto, 0, 1, 'L');
        }

        

        // Detalles del pago
        $pdf->Ln(3);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(145, 5, 'Concepto', 1, 0, 'C');
        $pdf->Cell(25, 5, 'Cantidad', 1, 0, 'C');
        $pdf->Cell(25, 5, 'Monto', 1, 1, 'C');

        $pdf->SetFont('Arial', '', 7);
        foreach ($pago->detalles as $detalle) {
            $pdf->Cell(145, 5, substr($detalle->descripcion, 0, 50), 1, 0);
            $pdf->Cell(25, 5, number_format($detalle->cantidad, 2, ',', '.'), 1, 0, 'R');

            // Convertir monto a bolívares si hay tasa de cambio
            $monto = $detalle->precio_unitario * $detalle->cantidad;
            if ($exchangeRate) {
                $montoBs = $monto * $exchangeRate->usd_rate;
                $pdf->Cell(25, 5, 'Bs. ' . number_format($montoBs, 2, ',', '.'), 1, 1, 'R');
            } else {
                $pdf->Cell(25, 5, '$' . number_format($monto, 2, ',', '.'), 1, 1, 'R');
            }
        }

        // Totales
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(170, 5, 'Subtotal:', 1, 0, 'R');
        if ($exchangeRate) {
            $subtotalBs = $pago->subtotal * $exchangeRate->usd_rate;
            $pdf->Cell(25, 5, 'Bs. ' . number_format($subtotalBs, 2, ',', '.'), 1, 1, 'R');
        } else {
            $pdf->Cell(25, 5, '$' . number_format($pago->subtotal, 2, ',', '.'), 1, 1, 'R');
        }

        if ($pago->descuento > 0) {
            $pdf->Cell(170, 5, 'Descuento:', 1, 0, 'R');
            if ($exchangeRate) {
                $descuentoBs = $pago->descuento * $exchangeRate->usd_rate;
                $pdf->Cell(25, 5, 'Bs. ' . number_format($descuentoBs, 2, ',', '.'), 1, 1, 'R');
            } else {
                $pdf->Cell(25, 5, '$' . number_format($pago->descuento, 2, ',', '.'), 1, 1, 'R');
            }
        }

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(170, 5, 'Total:', 1, 0, 'R');
        if ($exchangeRate) {
            $totalBs = $pago->total * $exchangeRate->usd_rate;
            $pdf->Cell(25, 5, 'Bs. ' . number_format($totalBs, 2, ',', '.'), 1, 1, 'R');
        } else {
            $pdf->Cell(25, 5, '$' . number_format($pago->total, 2, ',', '.'), 1, 1, 'R');
        }

        // Firma
        $pdf->Ln(4);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(90, 5, '', 0, 0); // Espacio en blanco
        $pdf->Cell(80, 5, '__________________________', 0, 1, 'C');
        $pdf->Cell(90, 5, '', 0, 0); // Espacio en blanco
        $pdf->Cell(80, 5, 'Firma y Sello', 0, 1, 'C');
    }

    public function closePreview()
    {
        $this->showPreview = false;
        $this->previewPagoId = null;
    }
}