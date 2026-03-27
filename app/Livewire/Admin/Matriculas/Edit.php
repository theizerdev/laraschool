<?php

namespace App\Livewire\Admin\Matriculas;

use App\Traits\HasDynamicLayout;
use App\Traits\HasRegionalFormatting;
use Livewire\Component;
use App\Models\Matricula;
use App\Models\Student;
use App\Models\Programa;
use App\Models\SchoolPeriod;
use App\Models\PaymentSchedule;

class Edit extends Component
{
    use HasDynamicLayout, HasRegionalFormatting;

     public $matricula;
    public $student_id;
    public $programa_id;
    public $periodo_id;
    public $fecha_matricula;
    public $estado;

    // Campos de costos
    public $costo;
    public $cuota_inicial;
    public $numero_cuotas;

    public $students = [];
    public $programas = [];
    public $periodos = [];

    // Búsqueda de estudiantes
    public $searchStudent = '';
    public $showStudentDropdown = false;

    // Tabla de amortización
    public $paymentSchedule = [];
    public $showSchedule = false;


    protected $rules = [
        'student_id' => 'required|exists:students,id',
        'programa_id' => 'required|exists:programas,id',
        'periodo_id' => 'required|exists:school_periods,id',
        'fecha_matricula' => 'required|date',
        'estado' => 'required|in:activo,inactivo,graduado',
        'costo' => 'required|numeric|min:0',
        'cuota_inicial' => 'required|numeric|min:0',
        'numero_cuotas' => 'required|integer|min:0'
    ];

  public function mount($matricula)
    {

        $this->matricula = Matricula::with(['estudiante', 'programa', 'periodo'])->findOrFail($matricula);

        $this->student_id = $this->matricula->estudiante_id;
        $this->programa_id = $this->matricula->programa_id;
        $this->periodo_id = $this->matricula->periodo_id;
        $this->fecha_matricula = $this->matricula->fecha_matricula ? $this->matricula->fecha_matricula->format('Y-m-d') : now()->format('Y-m-d');
        $this->estado = $this->matricula->estado ?? 'activo';
        $this->costo = $this->matricula->costo ?? 0;
        $this->cuota_inicial = $this->matricula->cuota_inicial ?? 0;
        $this->numero_cuotas = $this->matricula->numero_cuotas ?? 0;

        $this->searchStudent = $this->matricula->estudiante ? $this->matricula->estudiante->nombres . ' ' . $this->matricula->estudiante->apellidos : '';

        // Asegurar que el student_id esté correctamente establecido
        if (!$this->student_id && $this->matricula->estudiante) {
            $this->student_id = $this->matricula->estudiante->id;
        }

        $this->loadData();
        $this->loadPaymentSchedule();
        
        // Verificar si el período existe
        if ($this->periodo_id && !SchoolPeriod::find($this->periodo_id)) {
            session()->flash('warning', 'El período escolar asociado a esta matrícula no está disponible. Por favor, seleccione un período válido.');
        }
    }

     public function loadData()
    {
        $query = Student::query();

        if (!auth()->user()->hasRole('Super Administrador')) {
            $query->where('empresa_id', auth()->user()->empresa_id)
                  ->where('sucursal_id', auth()->user()->sucursal_id);
        }

        // Cargar estudiantes disponibles más el estudiante actual
        $this->students = $query->where(function($q) {
                $q->whereDoesntHave('matriculas')
                  ->orWhere('id', $this->student_id);
            })
            ->orderBy('nombres')
            ->orderBy('apellidos')
            ->get();

        $programaQuery = Programa::where('activo', true);
        if (!auth()->user()->hasRole('Super Administrador')) {
            $programaQuery->where('empresa_id', auth()->user()->empresa_id)
                         ->where('sucursal_id', auth()->user()->sucursal_id);
        }
        $this->programas = $programaQuery->orderBy('nombre')->get();

        // Cargar períodos, asegurando que el período actual esté incluido
        $periodoQuery = SchoolPeriod::query();
        
        // Si hay un período seleccionado, asegurarnos de que esté en la lista
        if ($this->periodo_id) {
            $periodoActual = SchoolPeriod::find($this->periodo_id);
            if ($periodoActual) {
                // Obtener todos los períodos ordenados, incluyendo el actual
                $this->periodos = $periodoQuery->orderBy('name')->get();
            } else {
                $this->periodos = $periodoQuery->orderBy('name')->get(); // Lista vacía si el período no existe
            }
        } else {
            $this->periodos = $periodoQuery->orderBy('name')->get();
        }
    }


    public function updatedSearchStudent()
    {
        if (strlen($this->searchStudent) >= 2) {
            $this->students = Student::whereDoesntHave('matriculas')
                ->with('nivelEducativo')
                ->where(function($query) {
                    $query->where('nombres', 'like', '%' . $this->searchStudent . '%')
                          ->orWhere('apellidos', 'like', '%' . $this->searchStudent . '%')
                          ->orWhere('documento_identidad', 'like', '%' . $this->searchStudent . '%');
                })
                ->orderBy('nombres')
                ->orderBy('apellidos')
                ->limit(10)
                ->get();
            $this->showStudentDropdown = true;
        } else {
            $this->students = [];
            $this->showStudentDropdown = false;
        }
    }

    public function selectStudent($studentId)
    {
        $student = Student::with('nivelEducativo')->find($studentId);
        if ($student) {
            $this->student_id = $student->id;
            $this->searchStudent = $student->nombres . ' ' . $student->apellidos;
            $this->showStudentDropdown = false;
            
            // Auto-completar costos del nivel educativo
            if ($student->nivelEducativo) {
                $this->costo = $student->nivelEducativo->costo;
                $this->cuota_inicial = $student->nivelEducativo->cuota_inicial;
                $this->numero_cuotas = $student->nivelEducativo->numero_cuotas;
            }
            
            $this->generatePaymentSchedule();
        }
    }

    public function clearStudentSelection()
    {
        $this->student_id = null;
        $this->searchStudent = '';
        $this->showStudentDropdown = false;
        $this->students = [];
        $this->costo = 0;
        $this->cuota_inicial = 0;
        $this->numero_cuotas = 0;
        $this->paymentSchedule = [];
        $this->showSchedule = false;
    }

    public function updatedStudentId($value)
    {
        if ($value) {
            $student = Student::with('nivelEducativo')->find($value);

            if ($student && $student->nivelEducativo) {
                $this->costo = $student->nivelEducativo->costo;
                $this->cuota_inicial = $student->nivelEducativo->cuota_inicial;
                $this->numero_cuotas = $student->nivelEducativo->numero_cuotas;
            }

            // Generar tabla de amortización cuando se selecciona un estudiante
            $this->generatePaymentSchedule();
        }
    }

    public function updatedCosto()
    {
        $this->generatePaymentSchedule();
    }

    public function updatedCuotaInicial()
    {
        $this->generatePaymentSchedule();
    }

    public function updatedNumeroCuotas()
    {
        $this->generatePaymentSchedule();
    }

    public function updatedPeriodoId()
    {
        $this->generatePaymentSchedule();
    }

    public function generatePaymentSchedule()
    {
        // Solo generar si tenemos todos los datos necesarios
        if (!$this->costo || !$this->periodo_id) {
            $this->paymentSchedule = [];
            $this->showSchedule = false;
            return;
        }

        $periodo = SchoolPeriod::find($this->periodo_id);
        if (!$periodo) {
            $this->paymentSchedule = [];
            $this->showSchedule = false;
            return;
        }

        // Calcular monto restante después de la cuota inicial
        $montoRestante = $this->costo - $this->cuota_inicial;

        // Si no hay cuotas, todo se cobra en la cuota inicial
        if ($this->numero_cuotas <= 0) {
            $this->paymentSchedule = [
                [
                    'numero_cuota' => 1,
                    'descripcion' => 'Pago único',
                    'monto' => $this->costo,
                    'fecha_vencimiento' => $periodo->start_date
                ]
            ];
            $this->showSchedule = true;
            return;
        }

        // Calcular monto por cuota
        $montoCuota = $montoRestante / $this->numero_cuotas;

        // Generar cuotas mensuales
        $this->paymentSchedule = [];

        // Agregar cuota inicial
        if ($this->cuota_inicial > 0) {
            $this->paymentSchedule[] = [
                'numero_cuota' => 0,
                'descripcion' => 'Cuota inicial',
                'monto' => $this->cuota_inicial,
                'fecha_vencimiento' => $periodo->start_date
            ];
        }

        // Agregar cuotas distribuidas uniformemente a lo largo del período escolar
        $startDate = new \DateTime($periodo->start_date);
        $endDate = new \DateTime($periodo->end_date);

        // Calcular intervalo total en días
        $totalDays = $startDate->diff($endDate)->days;

        // Para cada cuota, calcular la fecha de vencimiento distribuida uniformemente
        for ($i = 1; $i <= $this->numero_cuotas; $i++) {
            $dueDate = clone $startDate;

            // Calcular días entre cuotas (distribución uniforme)
            if ($this->numero_cuotas > 1) {
                $daysBetweenPayments = floor($totalDays / ($this->numero_cuotas - 1));
                $dueDate->modify('+' . ($daysBetweenPayments * ($i - 1)) . ' days');
            } else {
                // Si solo hay una cuota, colocarla a la mitad del período
                $daysBetweenPayments = floor($totalDays / 2);
                $dueDate->modify('+' . $daysBetweenPayments . ' days');
            }

            // Asegurarse de que la fecha no exceda la fecha final
            if ($dueDate > $endDate) {
                $dueDate = clone $endDate;
            }

            $this->paymentSchedule[] = [
                'numero_cuota' => $i,
                'descripcion' => 'Cuota ' . $i,
                'monto' => round($montoCuota, 2),
                'fecha_vencimiento' => $dueDate->format('Y-m-d')
            ];
        }

        $this->showSchedule = true;
    }

    public function loadPaymentSchedule()
    {
        if (class_exists('\App\Models\PaymentSchedule')) {
            $this->paymentSchedule = $this->matricula->paymentSchedules()
                ->orderBy('numero_cuota')
                ->get()
                ->map(function ($schedule) {
                    return [
                        'numero_cuota' => $schedule->numero_cuota,
                        'descripcion' => $schedule->numero_cuota == 0 ? 'Cuota inicial' : 'Cuota ' . $schedule->numero_cuota,
                        'monto' => $schedule->monto,
                        'fecha_vencimiento' => $schedule->fecha_vencimiento,
                        'estado' => $schedule->estado ?? 'pendiente'
                    ];
                })
                ->toArray();
        } else {
            $this->paymentSchedule = [];
        }

        $this->showSchedule = count($this->paymentSchedule) > 0;

        // Si no hay cronograma existente, generar uno nuevo
        if (count($this->paymentSchedule) == 0) {
            $this->generatePaymentSchedule();
        }
    }

    public function update()
    {
        // Verificar permiso para crear matrículas
        if (!auth()->user()->can('create matriculas')) {
            session()->flash('error', 'No tienes permiso para crear matrículas.');
            return;
        }

        $this->validate();

        try {
             $matricula = Matricula::find($this->matricula->id);
             $matricula->update([
                'empresa_id' => auth()->user()->empresa_id,
                'sucursal_id' => auth()->user()->sucursal_id,
                'estudiante_id' => $this->student_id,
                'programa_id' => $this->programa_id,
                'periodo_id' => $this->periodo_id,
                'fecha_matricula' => $this->fecha_matricula,
                'estado' => $this->estado,
                'costo' => $this->costo,
                'cuota_inicial' => $this->cuota_inicial,
                'numero_cuotas' => $this->numero_cuotas
            ]);

            // Generar cronograma de pagos si existe la clase
            if (class_exists('\App\Models\PaymentSchedule')) {
                $this->createPaymentSchedule($matricula);
            }

            // Enviar notificación WhatsApp de matrícula
            $whatsappResult = $this->enviarNotificacionMatricula($matricula);
           
            $mensaje = 'Matrícula creada correctamente.';
            if ($whatsappResult['sent']) {
                $mensaje .= ' Notificación enviada por WhatsApp a ' . $whatsappResult['destinatario'] . '.';
            } elseif ($whatsappResult['attempted']) {
                $mensaje .= ' No se pudo enviar notificación por WhatsApp.';
            }
           
            session()->flash('message', $mensaje);
            return redirect()->route('admin.matriculas.index');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al crear la matrícula: ' . $e->getMessage());
            \Log::error('Error creating matricula: ' . $e->getMessage());
        }
    }

    private function createPaymentSchedule($matricula)
    {
        try {
            $matricula = Matricula::find($this->matricula_id);
         
            foreach ($this->paymentSchedule as $schedule) {
                
                PaymentSchedule::where('matricula_id', $matricula->id)
                    ->updateOrCreate(
                        ['numero_cuota' => $schedule['numero_cuota']],
                        [
                            'monto' => $schedule['monto'],
                            'fecha_vencimiento' => $schedule['fecha_vencimiento'],
                    'estado' => 'pendiente',
                    'empresa_id' => auth()->user()->empresa_id,
                    'sucursal_id' => auth()->user()->sucursal_id,
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Error creating payment schedule: ' . $e->getMessage());
        }
    }

    private function enviarNotificacionMatricula($matricula)
    {
        $result = ['sent' => false, 'attempted' => false, 'destinatario' => null];
        
        try {
            $estudiante = $matricula->student;
            $esMayorDeEdad = \Carbon\Carbon::parse($estudiante->fecha_nacimiento)->age >= 18;
            
            $telefono = null;
            $nombreDestino = null;

            if (!$esMayorDeEdad && $estudiante->representante_telefonos) {
                $telefonos = explode(',', $estudiante->representante_telefonos);
        
                $telefono = trim($telefonos[0] ?? '');
                $nombreDestino = $estudiante->representante_nombres . ' ' . $estudiante->representante_apellidos;
            }
            else
                {
                    $telefono = $estudiante->telefono;
                    $nombreDestino = $estudiante->nombres . ' ' . $estudiante->apellidos;
                }

              

            if (!$telefono) return $result;

            $result['attempted'] = true;
            $result['destinatario'] = $nombreDestino;
            
            $mensaje = $this->generarMensajeMatricula($matricula, $estudiante, $esMayorDeEdad);
            $telefonoFormateado = $this->formatPhoneNumber($telefono);
            
            $whatsappService = app('App\\Services\\WhatsAppService');
            $whatsappResult = $whatsappService->sendMessage($telefonoFormateado, $mensaje);
            
            $result['sent'] = $whatsappResult && ($whatsappResult['success'] ?? false);
            
        } catch (\Exception $e) {
            \Log::error('Error enviando notificación WhatsApp de matrícula: ' . $e->getMessage());
            $result['attempted'] = true;
        }
        
        return $result;
    }

    private function generarMensajeMatricula($matricula, $estudiante, $esMayorDeEdad)
    {
        $nombreEstudiante = $estudiante->nombres . ' ' . $estudiante->apellidos;
        $costoFormateado = '$' . number_format($matricula->costo, 2, ',', '.');
        
        if ($esMayorDeEdad) {
            $mensaje = "🎓 *Matrícula Confirmada - U.E VARGAS II*\n\n";
            $mensaje .= "Estimado/a {$nombreEstudiante},\n\n";
            $mensaje .= "Su matrícula ha sido procesada exitosamente.\n\n";
        } else {
            $representante = $estudiante->representante_nombres . ' ' . $estudiante->representante_apellidos;
            $mensaje = "🎓 *Matrícula Confirmada - U.E VARGAS II*\n\n";
            $mensaje .= "Estimado/a {$representante},\n\n";
            $mensaje .= "La matrícula del estudiante *{$nombreEstudiante}* ha sido procesada exitosamente.\n\n";
        }
        
        $mensaje .= "📝 *Detalles de la Matrícula:*\n";
        $mensaje .= "• Programa: {$matricula->programa->nombre}\n";
        $mensaje .= "• Período: {$matricula->schoolPeriod->name}\n";
        $mensaje .= "• Fecha de Matrícula: " . \Carbon\Carbon::parse($matricula->fecha_matricula)->format('d/m/Y') . "\n";
        $mensaje .= "• Costo Total: {$costoFormateado}\n";
        
        if ($matricula->cuota_inicial > 0) {
            $cuotaInicialFormateada = '$' . number_format($matricula->cuota_inicial, 2, ',', '.');
            $mensaje .= "• Cuota Inicial: {$cuotaInicialFormateada}\n";
        }
        
        if ($matricula->numero_cuotas > 0) {
            $mensaje .= "• Número de Cuotas: {$matricula->numero_cuotas}\n";
        }
        
        $mensaje .= "\n💳 Próximamente recibirá información sobre las fechas de pago y métodos disponibles.\n\n";
        $mensaje .= "Gracias por confiar en nuestra institución.\n\n";
        $mensaje .= "*U.E VARGAS II*";
        
        return $mensaje;
    }

    private function formatPhoneNumber($number)
    {
        $empresa = \DB::table('empresas')->where('id', 1)->first();
        $pais = $empresa ? \DB::table('pais')->where('id', $empresa->pais_id)->first() : null;
        $codigoPais = $pais ? $pais->codigo_telefonico : '58';
        
        $cleaned = preg_replace('/[^0-9]/', '', $number);
        
        if (strlen($cleaned) > 10 && str_starts_with($cleaned, $codigoPais)) {
            return $cleaned;
        }
        
        if (str_starts_with($cleaned, '0')) {
            $cleaned = substr($cleaned, 1);
        }
        
        return $codigoPais . $cleaned;
    }

    

    public function render()
    {
        $selectedStudent = $this->student_id
            ? Student::withoutGlobalScopes()->with('nivelEducativo')->find($this->student_id)
            : null;

        return view('livewire.admin.matriculas.edit', [
            'selectedStudent' => $selectedStudent,
        ])->layout($this->getLayout());
    }
}
