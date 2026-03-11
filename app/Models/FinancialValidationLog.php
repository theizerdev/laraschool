<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialValidationLog extends Model
{
    use HasFactory, Multitenantable;

    protected $fillable = [
        'matricula_id',
        'estudiante_id',
        'periodo_id',
        'user_id',
        'solvente',
        'total_adeudado',
        'duracion_ms',
        'detalles',
        'empresa_id',
        'sucursal_id',
    ];

    protected $casts = [
        'solvente' => 'boolean',
        'total_adeudado' => 'decimal:2',
        'duracion_ms' => 'integer',
        'detalles' => 'array',
    ];

    public function matricula()
    {
        return $this->belongsTo(Matricula::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'estudiante_id');
    }

    public function periodo()
    {
        return $this->belongsTo(SchoolPeriod::class, 'periodo_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

