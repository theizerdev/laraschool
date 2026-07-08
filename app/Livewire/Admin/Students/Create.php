<?php

namespace App\Livewire\Admin\Students;

use App\Traits\HasDynamicLayout;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Student;
use App\Models\EducationalLevel;
use App\Models\Turno;
use App\Models\SchoolPeriod;
use App\Mail\StudentWelcomeMail;
use App\Mail\RepresentativeWelcomeMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Models\Notification;

class Create extends Component
{


    use WithFileUploads,HasDynamicLayout;

    public $nombres = '';
    public $apellidos = '';
    public $fecha_nacimiento = '';
    public $codigo = '';
    public $documento_identidad = '';
    public $grado = '';
    public $seccion = '';
    public $nivel_educativo_id = '';
    public $turno_id = '';
    public $school_periods_id = '';
    public $telefono = 0; // Se agrega el campo school_periods_id
    public $foto;
    public $correo_electronico = ''; // Nuevo campo para estudiantes mayores de edad
    public $status = true;
    public $useCamera = false;
    public $cameraImage = null;

    // Campos para representante (solo para menores de edad)
    public $representante_nombres = '';
    public $representante_apellidos = '';
    public $representante_documento_identidad = '';
    public $representante_telefonos = ''; // Se manejará como string separado por comas
    public $representante_correo = '';
    public $representante_direccion = ''; // Campo no obligatorio para dirección del domicilio

    protected $rules = [
        'nombres' => 'required|string|max:255',
        'apellidos' => 'required|string|max:255',
        'fecha_nacimiento' => 'required|date|before:today',
        'codigo' => 'required|string|unique:students,codigo|size:8',
        'documento_identidad' => 'required|string|unique:students,documento_identidad',
        'grado' => 'required|string|max:50',
        
        'seccion' => 'required|string|max:10',
        'nivel_educativo_id' => 'required|exists:niveles_educativos,id',
        'turno_id' => 'required|exists:turnos,id',
        'school_periods_id' => 'required|exists:school_periods,id',
        'foto' => 'nullable|image|max:2048',
        'correo_electronico' => 'nullable|email|max:255', // Validación para estudiantes mayores de edad
        'status' => 'boolean',
        // Validaciones para representante (solo para menores de edad)
        'representante_nombres' => 'nullable|string|max:255',
        'representante_apellidos' => 'nullable|string|max:255',
        'representante_documento_identidad' => 'nullable|string',
        'representante_telefonos' => 'nullable|string|max:255',
        'representante_correo' => 'nullable|email|max:255',
        'representante_direccion' => 'nullable|string|max:500',
    ];

    protected $messages = [
        'fecha_nacimiento.before' => 'La fecha de nacimiento no puede ser una fecha futura.',
        'codigo.unique' => 'Este código ya está en uso.',
        'documento_identidad.unique' => 'Este documento de identidad ya está registrado.',
    ];

    public function mount()
    {
        // Verificar permiso para crear estudiantes
        if (!Auth::user()->can('create students')) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        // Generar código aleatorio de 8 dígitos
        $this->generateCode();
    }

    public function generateCode()
    {
        do {
            $this->codigo = str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        } while (Student::where('codigo', $this->codigo)->exists());
    }

    public function toggleCamera()
    {
        $this->useCamera = !$this->useCamera;
    }

    // Función para verificar si el estudiante es menor de edad
    public function getEsMenorDeEdadProperty()
    {
        if ($this->fecha_nacimiento) {
            try {
                $fechaNacimiento = Carbon::createFromFormat('Y-m-d', $this->fecha_nacimiento);
                return $fechaNacimiento->age < 18;
            } catch (\Exception $e) {
                return false;
            }
        }
        return false;
    }

    // Función para obtener la edad con años y meses
    public function getEdadConMesesProperty()
    {
        if ($this->fecha_nacimiento) {
            try {
                $fechaNacimiento = Carbon::createFromFormat('Y-m-d', $this->fecha_nacimiento);
                $now = Carbon::now();

                // Asegurarse de que la fecha de nacimiento no sea futura
                if ($fechaNacimiento->isFuture()) {
                    return 'Fecha inválida';
                }

                // Calcular diferencia usando Carbon de forma precisa
                $years = $now->diff($fechaNacimiento)->y;
                $months = $now->diff($fechaNacimiento)->m;

                if ($years < 10) {
                    if ($months == 0) {
                        return "$years año" . ($years != 1 ? 's' : '');
                    }
                    return "$years año" . ($years != 1 ? 's' : '') . " y $months mes" . ($months != 1 ? 'es' : '');
                }

                return "$years años";
            } catch (\Exception $e) {
                return 'Edad no disponible';
            }
        }
        return 'Fecha no especificada';
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

            if (!$telefono) return $result;

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

    public function save()
    {
       try {
         $this->validate();

        $fotoPath = null;
        if ($this->foto) {
            $fotoPath = $this->foto->store('students', 'public');
        }

        $student = Student::create([
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'fecha_nacimiento' => $this->fecha_nacimiento,
            'codigo' => $this->codigo,
            'documento_identidad' => $this->documento_identidad,
            'grado' => $this->grado,
            'seccion' => $this->seccion,
            'nivel_educativo_id' => $this->nivel_educativo_id,
            'turno_id' => $this->turno_id,
            'school_periods_id' => $this->school_periods_id,
            'foto' => $fotoPath,
            'correo_electronico' => $this->correo_electronico, // Guardar correo para estudiantes mayores
            'status' => $this->status,
            'telefono' => $this->telefono,
            // Datos del representante (solo para menores de edad)
            'representante_nombres' => $this->representante_nombres,
            'representante_apellidos' => $this->representante_apellidos,
            'representante_documento_identidad' => $this->representante_documento_identidad,
            'representante_telefonos' => $this->representante_telefonos,
            'representante_correo' => $this->representante_correo,
            'representante_direccion' => $this->representante_direccion,
        ]);

       

        // Enviar correo de bienvenida si el estudiante es mayor de edad y tiene correo
        if (!$this->esMenorDeEdad && $this->correo_electronico) {
            try {
                Mail::to($this->correo_electronico)->send(new StudentWelcomeMail($student));
            } catch (\Exception $e) {
                // Registrar el error pero no detener el proceso de registro
                \Log::error('Error al enviar correo de bienvenida al estudiante: ' . $e->getMessage());
                session()->flash('warning', 'Estudiante creado correctamente, pero hubo un error al enviar el correo de bienvenida. Puedes reenviarlo más tarde desde la lista de estudiantes.');
            }
        }

        // Enviar correo de bienvenida al representante si el estudiante es menor de edad y tiene correo
        if ($this->esMenorDeEdad && $this->representante_correo) {
            try {
                Mail::to($this->representante_correo)->send(new RepresentativeWelcomeMail($student));
            } catch (\Exception $e) {
                // Registrar el error pero no detener el proceso de registro
                \Log::error('Error al enviar correo de bienvenida al representante: ' . $e->getMessage());
                session()->flash('warning', 'Estudiante creado correctamente, pero hubo un error al enviar el correo de bienvenida al representante. Puedes reenviarlo más tarde desde la lista de estudiantes.');
            }
        }

        // Enviar notificación WhatsApp de bienvenida
        $whatsappResult = $this->enviarNotificacionBienvenida($student);
        
        $mensaje = 'Estudiante creado correctamente.';
        if ($whatsappResult['sent']) {
            $mensaje .= ' Mensaje de bienvenida enviado por WhatsApp a ' . $whatsappResult['destinatario'] . '.';
        } elseif ($whatsappResult['attempted']) {
            $mensaje .= ' No se pudo enviar mensaje de bienvenida por WhatsApp.';
        }

        // Crear notificación
        Notification::create([
            'user_id' => auth()->id(),
            'type' => 'success',
            'title' => 'Estudiante registrado',
            'message' => "El estudiante {$student->nombres} {$student->apellidos} ha sido registrado exitosamente",
            'data' => ['student_id' => $student->id]
        ]);

        // Disparar evento para actualizar en tiempo real
        $this->dispatch('notification-created');

        session()->flash('message', $mensaje);
        return redirect()->route('admin.students.index');
       } catch (\Throwable $th) {
        throw $th;
       }
    }

    public function render()
    {
        return $this->renderWithLayout('livewire.admin.students.create', [
            'nivelesEducativos' => EducationalLevel::all(),
            'turnos' => Turno::all(),
            'schoolPeriods' => SchoolPeriod::all(),
        ], [
            'description' => 'Gestión de ',
        ]);
    }
}
