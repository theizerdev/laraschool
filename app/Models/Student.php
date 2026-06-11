<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use App\Models\StudentAccessLog;
use App\Traits\Multitenantable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Student extends Model
{
    use HasFactory, Multitenantable, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'empresa_id',
        'sucursal_id',
        'nombres',
        'apellidos',
        'fecha_nacimiento',
        'codigo',
        'documento_identidad',
        'grado',
        'seccion',
        'nivel_educativo_id',
        'turno_id',
        'school_periods_id',
        'foto',
        'correo_electronico',
        'telefono',
        'status',
        'representante_nombres',
        'representante_apellidos',
        'representante_documento_identidad',
        'representante_telefonos',
        'representante_correo',
        'representante_direccion',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_nacimiento' => 'date:Y-m-d',
        'status' => 'boolean',
        'representante_telefonos' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the educational level that owns the student.
     */
    public function nivelEducativo()
    {
        return $this->belongsTo(EducationalLevel::class, 'nivel_educativo_id');
    }

    /**
     * Get the shift that owns the student.
     */
    public function turno()
    {
        return $this->belongsTo(Turno::class, 'turno_id');
    }

    /**
     * Get the school period that owns the student.
     */
    public function schoolPeriod()
    {
        return $this->belongsTo(SchoolPeriod::class, 'school_periods_id');
    }

    /**
     * Get the student's matriculas.
     */
    public function matriculas()
    {
        return $this->hasMany(Matricula::class, 'estudiante_id');
    }

    /**
     * Get the student's academic records.
     */
    public function academicRecords()
    {
        return $this->hasMany(AcademicRecord::class, 'student_id');
    }

    /**
     * Get the student's age in years.
     */
    public function getEdadAttribute()
    {
        if (!$this->fecha_nacimiento) {
            return null;
        }

        try {
            return Carbon::parse($this->fecha_nacimiento)->age;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the student's age in years and months.
     */
    public function getEdadConMesesAttribute()
    {
        if (!$this->fecha_nacimiento) {
            return 'Fecha no especificada';
        }

        try {
            $now = Carbon::now();
            $birthday = Carbon::parse($this->fecha_nacimiento);

            // Asegurarse de que la fecha de nacimiento no sea futura
            if ($birthday->isFuture()) {
                return 'Fecha inválida';
            }

            // Calcular diferencia usando Carbon de forma precisa
            $years = $now->diff($birthday)->y;
            $months = $now->diff($birthday)->m;

            if ($years < 10) {
                if ($months == 0) {
                    return "$years año" . ($years != 1 ? 's' : '');
                }
                return "$years año" . ($years != 1 ? 's' : '') . " y $months mes" . ($months != 1 ? 'es' : '');
            }

            return "$years años";
        } catch (\Exception $e) {
            return 'Fecha inválida';
        }
    }

    /**
     * Check if the student is a minor.
     */
    public function getEsMenorDeEdadAttribute()
    {
        if (!$this->fecha_nacimiento) {
            return false;
        }

        try {
            return Carbon::parse($this->fecha_nacimiento)->age < 18;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the contact phone number based on student's age.
     * For adults (18+), returns student's phone, otherwise returns representative's phone.
     */
    public function getTelefonoContactoAttribute()
    {
        // Si es mayor de edad y tiene teléfono propio, usar ese
        if (!$this->esMenorDeEdad && !empty($this->telefono)) {
            return $this->telefono;
        }

        // Si es menor de edad o no tiene teléfono propio, usar el del representante
        if (!empty($this->representante_telefonos) && is_array($this->representante_telefonos)) {
            return $this->representante_telefonos[0] ?? null;
        }

        return null;
    }

    /**
     * Get all contact phone numbers for notifications.
     * For adults: returns their own phone number.
     * For minors: returns representative's phone numbers.
     */
    public function getTelefonosParaNotificacionAttribute()
    {
        $telefonos = [];

        // Si es mayor de edad y tiene teléfono propio
        if (!$this->esMenorDeEdad && !empty($this->telefono)) {
            $telefonos[] = $this->telefono;
        }

        // Si es menor de edad o no tiene teléfono propio, usar los del representante
        if($this->esMenorDeEdad && !empty($this->representante_telefonos) && is_array($this->representante_telefonos)) {
            $telefonos = array_merge($telefonos, $this->representante_telefonos);
        }

        return array_filter($telefonos); // Eliminar valores vacíos
    }

    /**
     * Obtener datos del representante como un objeto estructurado
     */
    public function getRepresentanteAttribute(): ?array
    {
        // Si no hay datos del representante, retornar null
        if(empty($this->representante_nombres) && empty($this->representante_apellidos)) {
            return null;
        }

        return [
            'nombres' => $this->representante_nombres ?? '',
            'apellidos' => $this->representante_apellidos ?? '',
            'nombre_completo' => trim(($this->representante_nombres ?? '') . ' ' . ($this->representante_apellidos ?? '')),
            'documento_identidad' => $this->representante_documento_identidad ?? '',
            'telefono' => is_array($this->representante_telefonos) ? ($this->representante_telefonos[0] ?? '') : ($this->representante_telefonos ?? ''),
            'correo' => $this->representante_correo ?? '',
            'direccion' => $this->representante_direccion ?? '',
        ];
    }

    /**
     * Generate QR code for the student
     */
    public function generateQrCode($size = 100)
    {
        try {
            $renderer = new ImageRenderer(
                new RendererStyle($size),
                new SvgImageBackEnd()
            );
            $writer = new Writer($renderer);

            // Generar código QR con la información del estudiante
            $data = "Código: {$this->codigo}\n";
            $data .= "Nombre: {$this->nombres} {$this->apellidos}\n";
            $data .= "Documento: {$this->documento_identidad}\n";
            $data .= "Grado: {$this->grado} - {$this->seccion}";

            return 'data:image/svg+xml;base64,' . base64_encode($writer->writeString($data));
        } catch (\Exception $e) {
            // En caso de error, devolver un SVG simple con un cuadrado
            return 'data:image/svg+xml;base64,' . base64_encode('<svg width="' . $size . '" height="' . $size . '" xmlns="http://www.w3.org/2000/svg"><rect width="100%" height="100%" fill="lightgray"/><text x="50%" y="50%" font-family="Arial" font-size="12" text-anchor="middle" fill="gray">QR no disponible</text></svg>');
        }
    }

    /**
     * Convert SVG QR code to PNG
     */
    public function generateQrCodePng($size = 100)
    {
        try {
            // Usar directamente el backend PNG de BaconQrCode
            $renderer = new ImageRenderer(
                new RendererStyle($size),
                new \BaconQrCode\Renderer\Image\SimpleImageBackEnd() // Usar SimpleImageBackEnd que no requiere Imagick
            );
            $writer = new Writer($renderer);

            // Generar código QR con la información del estudiante
            $data = "Código: {$this->codigo}\n";
            $data .= "Nombre: {$this->nombres} {$this->apellidos}\n";
            $data .= "Documento: {$this->documento_identidad}\n";
            $data .= "Grado: {$this->grado} - {$this->seccion}";

            // Devolver directamente la imagen PNG
            return 'data:image/png;base64,' . base64_encode($writer->writeString($data));
        } catch (\Exception $e) {
            \Log::error('Error generando código QR PNG: ' . $e->getMessage());

            // En caso de error, crear una imagen PNG simple con texto
            $image = imagecreate($size, $size);
            $bgColor = imagecolorallocate($image, 211, 211, 211); // Gris claro
            $textColor = imagecolorallocate($image, 128, 128, 128); // Gris
            imagefilledrectangle($image, 0, 0, $size, $size, $bgColor);
            imagestring($image, 2, 10, $size/2 - 10, 'QR no disponible', $textColor);

            // Crear un buffer para almacenar la imagen PNG
            ob_start();
            imagepng($image);
            $pngContent = ob_get_contents();
            ob_end_clean();

            // Liberar la imagen
            imagedestroy($image);

            return 'data:image/png;base64,' . base64_encode($pngContent);
        }
    }

    /**
     * Get the access logs for the student.
     */
    public function accessLogs()
    {
        return $this->hasMany(StudentAccessLog::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nombres', 'apellidos', 'codigo', 'documento_identidad', 'grado', 'seccion', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Scope a query to only include active students.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope a query to only include students by educational level.
     */
    public function scopeByEducationalLevel($query, $levelId)
    {
        return $query->where('nivel_educativo_id', $levelId);
    }

    /**
     * Scope a query to only include students by grade.
     */
    public function scopeByGrade($query, $grade)
    {
        return $query->where('grado', $grade);
    }

    /**
     * Scope a query to only include students by section.
     */
    public function scopeBySection($query, $section)
    {
        return $query->where('seccion', $section);
    }
}
