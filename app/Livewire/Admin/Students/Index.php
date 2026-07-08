<?php

namespace App\Livewire\Admin\Students;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Student;
use App\Models\EducationalLevel;
use App\Models\Turno;
use App\Models\SchoolPeriod;
use App\Models\Empresa;
use App\Models\Sucursal;
use App\Models\Matricula;
use App\Models\PaymentSchedule;
use App\Traits\Exportable;
use App\Mail\StudentWelcomeMail;
use App\Mail\RepresentativeWelcomeMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\Carbon;
use App\Traits\HasDynamicLayout;

class Index extends Component
{
    use WithPagination, Exportable,HasDynamicLayout;

    public $search = '';
    public $status = '';
    public $empresa_id = '';
    public $sucursal_id = '';
    public $nivelEducativoId = '';
    public $turnoId = '';
    public $schoolPeriodId = '';
    public $grado = '';
    public $seccion = '';
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 10;
    public $showQrModal = false;
    public $selectedStudent = null;
    public $exportFormat = 'excel'; // Nuevo: formato de exportación

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'empresa_id' => ['except' => ''],
        'sucursal_id' => ['except' => ''],
        'nivelEducativoId' => ['except' => ''],
        'turnoId' => ['except' => ''],
        'schoolPeriodId' => ['except' => ''],
        'grado' => ['except' => ''],
        'seccion' => ['except' => ''],
        'sortBy' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
        'perPage' => ['except' => 10]
    ];

    public function mount()
    {
        // Verificar permiso para ver estudiantes
        if (!Auth::user()->can('access students')) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function updatingNivelEducativoId()
    {
        $this->resetPage();
    }

    public function updatingTurnoId()
    {
        $this->resetPage();
    }

    public function updatingSchoolPeriodId()
    {
        $this->resetPage();
    }

    public function updatingGrado()
    {
        $this->resetPage();
    }

    public function updatingSeccion()
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
    }

    public function render()
    {
        $students = Student::query()
            ->with(['nivelEducativo', 'turno', 'schoolPeriod', 'empresa', 'sucursal'])
            ->when($this->search, function ($query) {
                $query->where('nombres', 'like', '%' . $this->search . '%')
                    ->orWhere('apellidos', 'like', '%' . $this->search . '%')
                    ->orWhere('codigo', 'like', '%' . $this->search . '%')
                    ->orWhere('documento_identidad', 'like', '%' . $this->search . '%');
            })
            ->when($this->status !== '', function ($query) {
                $query->where('status', $this->status);
            })
            ->when($this->empresa_id, function ($query) {
                $query->where('empresa_id', $this->empresa_id);
            })
            ->when($this->sucursal_id, function ($query) {
                $query->where('sucursal_id', $this->sucursal_id);
            })
            ->when($this->nivelEducativoId, function ($query) {
                $query->where('nivel_educativo_id', $this->nivelEducativoId);
            })
            ->when($this->turnoId, function ($query) {
                $query->where('turno_id', $this->turnoId);
            })
            ->when($this->schoolPeriodId, function ($query) {
                $query->where('school_periods_id', $this->schoolPeriodId);
            })
            ->when($this->grado, function ($query) {
                $query->where('grado', $this->grado);
            })
            ->when($this->seccion, function ($query) {
                $query->where('seccion', $this->seccion);
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);

        $empresas = Empresa::forUser()->get();
        $sucursales = Sucursal::forUser()->get();
        $nivelesEducativos = EducationalLevel::query()->get();
        $turnos = Turno::query()->get();
        $schoolPeriods = SchoolPeriod::query()->get();
        $grados = Student::query()->select('grado')->distinct()->pluck('grado');
        $secciones = Student::query()->select('seccion')->distinct()->pluck('seccion');

        // Calcular estadísticas
        $totalStudents = Student::query()->count();
        $activeStudents = Student::query()->where('status', 1)->count();
        $inactiveStudents = Student::query()->where('status', 0)->count();

        return $this->renderWithLayout('livewire.admin.students.index', compact(
            'students',
            'empresas',
            'sucursales',
            'nivelesEducativos',
            'turnos',
            'schoolPeriods',
            'grados',
            'secciones',
            'totalStudents',
            'activeStudents',
            'inactiveStudents'
        ), [
            'title' => 'Estudiantes',
            'description' => 'Gestión de estudiantes del sistema',
            'breadcrumb' => [
                'admin.dashboard' => 'Dashboard',
                'admin.students.index' => 'Estudiantes'
            ]
        ]);
    }

    public function delete(Student $student)
    {
        // Verificar permiso para eliminar estudiantes
        if (!Auth::user()->can('delete students')) {
            session()->flash('error', 'No tienes permiso para eliminar estudiantes.');
            return;
        }

        $student->delete();
        session()->flash('message', 'Estudiante eliminado correctamente.');
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->status = '';
        $this->empresa_id = '';
        $this->sucursal_id = '';
        $this->nivelEducativoId = '';
        $this->turnoId = '';
        $this->schoolPeriodId = '';
        $this->grado = '';
        $this->seccion = '';
        $this->sortBy = 'created_at';
        $this->sortDirection = 'desc';
        $this->perPage = 10;
        $this->resetPage();
    }

    // Nueva función para exportación avanzada
    public function exportAdvanced()
    {
        if (!Auth::user()->can('export students')) {
            session()->flash('error', 'No tienes permiso para exportar estudiantes.');
            return;
        }

        // Usar el método export del trait Exportable directamente
        return $this->export();
    }

    protected function getExportQuery()
    {
        return Student::query()
            ->with(['nivelEducativo', 'turno', 'schoolPeriod', 'empresa', 'sucursal'])
            ->when($this->search, fn($q) => $q->where('nombres', 'like', "%{$this->search}%")
                ->orWhere('apellidos', 'like', "%{$this->search}%"))
            ->when($this->status !== '', fn($q) => $q->where('status', $this->status))
            ->when($this->empresa_id, fn($q) => $q->where('empresa_id', $this->empresa_id))
            ->when($this->sucursal_id, fn($q) => $q->where('sucursal_id', $this->sucursal_id))
            ->when($this->nivelEducativoId, fn($q) => $q->where('nivel_educativo_id', $this->nivelEducativoId))
            ->when($this->turnoId, fn($q) => $q->where('turno_id', $this->turnoId))
            ->when($this->grado, fn($q) => $q->where('grado', $this->grado))
            ->when($this->seccion, fn($q) => $q->where('seccion', $this->seccion));
    }

    protected function getExportHeaders()
    {
        return [
            'Código',
            'Nombres',
            'Apellidos',
            'Documento',
            'Fecha Nacimiento',
            'Edad',
            'Grado',
            'Sección',
            'Nivel Educativo',
            'Turno',
            'Período Escolar',
            'Empresa',
            'Sucursal',
            'Estado',
            'Correo Electrónico',
            'Representante',
            'Teléfonos Representante',
            'Correo Representante',
            'Monto Total Matrícula',
            'Monto Pagado',
            'Monto Pendiente',
            'Próxima Fecha de Vencimiento',
            'Días de Retraso'
        ];
    }

    protected function formatExportRow($student)
    {
        // Obtener información de morosidad
        $debtInfo = $this->getStudentDebtInfo($student);

        // Formatear teléfonos del representante
        $telefonos = '';
        if ($student->representante_telefonos) {
            if (is_array($student->representante_telefonos)) {
                $telefonos = implode(', ', $student->representante_telefonos);
            } else {
                $telefonos = $student->representante_telefonos;
            }
        }

        return [
            $student->codigo,
            $student->nombres,
            $student->apellidos,
            $student->documento_identidad,
            $student->fecha_nacimiento ? $student->fecha_nacimiento->format('d/m/Y') : '',
            $student->edad ?? '',
            $student->grado,
            $student->seccion,
            $student->nivelEducativo->nombre ?? '',
            $student->turno->nombre ?? '',
            $student->schoolPeriod->nombre ?? '',
            $student->empresa->razon_social ?? '',
            $student->sucursal->nombre ?? '',
            $student->status ? 'Activo' : 'Inactivo',
            $student->correo_electronico ?? '',
            $student->representante_nombres ? $student->representante_nombres . ' ' . $student->representante_apellidos : '',
            $telefonos,
            $student->representante_correo ?? '',
            $debtInfo['total_amount'],
            $debtInfo['paid_amount'],
            $debtInfo['pending_amount'],
            $debtInfo['next_due_date'],
            $debtInfo['days_overdue']
        ];
    }

    // Función para obtener información de morosidad del estudiante
    private function getStudentDebtInfo($student)
    {
        // Obtener la matrícula activa del estudiante
        $matricula = Matricula::where('estudiante_id', $student->id)
            ->where('estado', 'activo')
            ->with('paymentSchedules') // Esta relación ya ha sido corregida en el modelo
            ->first();

        if (!$matricula) {
            return [
                'total_amount' => 0,
                'paid_amount' => 0,
                'pending_amount' => 0,
                'next_due_date' => '',
                'days_overdue' => 0
            ];
        }

        // Calcular totales
        $totalAmount = $matricula->paymentSchedules->sum('monto');
        $paidAmount = $matricula->paymentSchedules->sum('monto_pagado');
        $pendingAmount = $totalAmount - $paidAmount;

        // Obtener próxima fecha de vencimiento
        $nextDueDate = $matricula->paymentSchedules
            ->where('estado', '!=', 'pagado')
            ->where('fecha_vencimiento', '!=', null)
            ->min('fecha_vencimiento');

        // Calcular días de retraso
        $daysOverdue = 0;
        if ($nextDueDate) {
            $nextDueDate = Carbon::parse($nextDueDate);
            if ($nextDueDate->isPast()) {
                $daysOverdue = $nextDueDate->diffInDays(Carbon::now());
            }
        }

        return [
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount ?? 0,
            'pending_amount' => $pendingAmount,
            'next_due_date' => $nextDueDate ? $nextDueDate->format('d/m/Y') : '',
            'days_overdue' => $daysOverdue
        ];
    }

    public function sendWelcomeEmail(Student $student)
    {
        // Verificar permiso para enviar correos
        if (!Auth::user()->can('edit students')) {
            session()->flash('error', 'No tienes permiso para enviar correos de bienvenida.');
            return;
        }

        try {
            // Para estudiantes mayores de edad con correo
            if (!$student->esMenorDeEdad && $student->correo_electronico) {
                $this->enviarNotificacionBienvenida($student);
                session()->flash('message', 'Correo de bienvenida enviado al estudiante.');
            }
            // Para estudiantes menores de edad con correo de representante
            elseif ($student->esMenorDeEdad) {
                $this->enviarNotificacionBienvenida($student);
                session()->flash('message', 'Correo de bienvenida enviado al representante.');
            }
            // Si no hay correo al que enviar
             else
                {
                    $this->enviarNotificacionBienvenida($student);
                }
        } catch (\Exception $e) {
            \Log::error('Error al enviar correo de bienvenida: ' . $e->getMessage());
            session()->flash('error', 'Error al enviar el correo de bienvenida: ' . $e->getMessage() . '. Por favor, inténtelo más tarde.');
        }

        $this->resetPage();
    }

    public function showQrCode(Student $student)
    {
        $this->selectedStudent = $student;
        $this->showQrModal = true;
    }

    public function closeQrModal()
    {
        $this->showQrModal = false;
        $this->selectedStudent = null;
    }

    public function downloadQrCode(Student $student)
    {
        // Verificar permiso para descargar QR
        if (!Auth::user()->can('edit students')) {
            session()->flash('error', 'No tienes permiso para descargar el código QR.');
            return;
        }
        // Generar código QR en formato PNG
        $qrCode = $student->generateQrCode(300);
        $imageData = base64_decode(substr($qrCode, strpos($qrCode, ",") + 1));

        // Nombre del archivo
        $filename = 'qr_' . $student->codigo . '_' . str_replace(' ', '_', $student->nombres) . '_' . str_replace(' ', '_', $student->apellidos) . '.svg';

        // Enviar respuesta con la imagen PNG
        return response()->streamDownload(function () use ($imageData) {
            echo $imageData;
        }, $filename, [
            'Content-Type' => 'image/svg',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]);
    }

    private function enviarNotificacionBienvenida($student)
    {
        $result = ['sent' => false, 'attempted' => false, 'destinatario' => null];
        
        try {
            $esMayorDeEdad = Carbon::parse($student->fecha_nacimiento)->age >= 18;
            $telefono = null;
            $nombreDestino = null;

            if (!$esMayorDeEdad && $student->representante_telefonos) {
                $telefonos = explode(',', $student->representante_telefonos);
                $telefono = trim($telefonos[0] ?? '');
                $nombreDestino = $student->representante_nombres . ' ' . $student->representante_apellidos;
            }
            else
                {
                    $telefono = $student->telefono;
                    $nombreDestino = $student->nombres . ' ' . $student->apellidos;
                }



            $result['attempted'] = true;
            $result['destinatario'] = $nombreDestino;
            
            $mensaje = $this->generarMensajeBienvenida($student, $nombreDestino);
            $telefonoFormateado = $this->formatPhoneNumber($telefono);
            
            $whatsappService = app('App\\Services\\WhatsAppService');
            $whatsappResult = $whatsappService->sendMessage($telefonoFormateado, $mensaje);
            
            $result['sent'] = $whatsappResult && ($whatsappResult['success'] ?? false);
            
        } catch (\Exception $e) {
            \Log::error('Error enviando notificación WhatsApp de bienvenida: ' . $e->getMessage());
            $result['attempted'] = true;
        }
        
        return $result;
    }

    private function generarMensajeBienvenida($student, $nombreDestino)
    {
        $nombreEstudiante = $student->nombres . ' ' . $student->apellidos;
        
        $mensaje = "🎉 *¡Bienvenidos al U.E JOSE MARIA VARGAS!*\n\n";
        $mensaje .= "Estimado/a {$nombreDestino},\n\n";
        $mensaje .= "Nos complace informarle que el estudiante *{$nombreEstudiante}* ha sido registrado exitosamente en nuestra institución.\n\n";
        $mensaje .= "📝 *Datos del Estudiante:*\n";
        $mensaje .= "• Código: {$student->codigo}\n";
        $mensaje .= "• Documento: {$student->documento_identidad}\n";
        $mensaje .= "• Grado: {$student->grado} - Sección: {$student->seccion}\n";
        $mensaje .= "\n📚 Estamos comprometidos con brindar una educación de calidad y acompañar a nuestros estudiantes en su crecimiento académico y personal.\n\n";
        $mensaje .= "Próximamente recibirá información sobre el proceso de matrícula y demás detalles importantes.\n\n";
        $mensaje .= "Gracias por confiar en nosotros.\n\n";
        $mensaje .= "*U.E JOSE MARIA VARGAS*";
        
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
}
