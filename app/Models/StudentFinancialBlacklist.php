<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentFinancialBlacklist extends Model
{
    use HasFactory, Multitenantable;

    protected $fillable = [
        'student_id',
        'motivo',
        'activo',
        'created_by',
        'empresa_id',
        'sucursal_id',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

