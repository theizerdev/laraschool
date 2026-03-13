<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Multitenantable;

class Pago extends Model
{
    use HasFactory, Multitenantable, SoftDeletes;

    const TIPO_FACTURA = 'factura';
    const TIPO_BOLETA = 'boleta';
    const TIPO_NOTA_CREDITO = 'nota_credito';
    const TIPO_RECIBO = 'recibo';

    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_APROBADO = 'aprobado';
    const ESTADO_CANCELADO = 'cancelado';

    protected $fillable = [
        'caja_id',
        'serie_id',
        'serie',
        'numero',
        'tipo_pago',
        'fecha',
        'matricula_id',
        'user_id',
        'subtotal',
        'descuento',
        'total',
        'tasa_cambio',
        'total_bolivares',
        'metodo_pago',
        'referencia',
        'es_pago_mixto',
        'detalles_pago_mixto',
        'estado',
        'observaciones',
        'empresa_id',
        'sucursal_id'
    ];

    protected $casts = [
        'fecha' => 'date',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total' => 'decimal:2',
        'tasa_cambio' => 'decimal:4',
        'total_bolivares' => 'decimal:2',
        'es_pago_mixto' => 'boolean',
        'detalles_pago_mixto' => 'array'
    ];

    protected $attributes = [
        'estado' => self::ESTADO_PENDIENTE,
        'subtotal' => 0,
        'descuento' => 0
    ];

    public function detalles()
    {
        return $this->hasMany(PagoDetalle::class);
    }

    public function matricula()
    {
        return $this->belongsTo(Matricula::class);
    }

    // Alias para obtener el cliente (estudiante) a través de la matrícula
    public function getClienteAttribute()
    {
        return $this->matricula ? $this->matricula->student : null;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function comprobante()
    {
        return $this->morphOne(Comprobante::class, 'comprobanteable');
    }

    public static function getTipos()
    {
        return [
            self::TIPO_FACTURA => 'Factura',
            self::TIPO_BOLETA => 'Boleta',
            self::TIPO_NOTA_CREDITO => 'Nota de Crédito',
            self::TIPO_RECIBO => 'Recibo'
        ];
    }

    public static function getEstados()
    {
        return [
            self::ESTADO_PENDIENTE => 'Pendiente',
            self::ESTADO_APROBADO => 'Aprobado',
            self::ESTADO_CANCELADO => 'Cancelado'
        ];
    }

    public function getNumeroCompletoAttribute()
    {
        if ($this->serieModel) {
            return $this->serieModel->serie . '-' . str_pad($this->numero, $this->serieModel->longitud_correlativo, '0', STR_PAD_LEFT);
        }
        return $this->serie . '-' . $this->numero;
    }

    public function serieModel()
    {
        return $this->belongsTo(Serie::class, 'serie_id');
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class);
    }

    public function conceptoPago()
    {
        return $this->belongsToMany(ConceptoPago::class, 'pago_detalles', 'pago_id', 'concepto_pago_id')
            ->withPivot('cantidad', 'precio_unitario', 'subtotal');
    }

    public static function generarNumero($tipo, $empresaId, $sucursalId, $serieId = null)
    {
        if ($serieId) {
            $serieModel = Serie::find($serieId);
        } else {
            $serieModel = Serie::where('tipo_documento', $tipo)
                ->where('empresa_id', $empresaId)
                ->where('sucursal_id', $sucursalId)
                ->where('activo', true)
                ->first();
        }

        // Si no existe serie, crear una automáticamente
        if (!$serieModel) {
            $prefijos = [
                'factura' => 'F001',
                'boleta' => 'B001', 
                'nota_credito' => 'NC01',
                'recibo' => 'R001'
            ];
            
            $serieModel = Serie::create([
                'tipo_documento' => $tipo,
                'serie' => $prefijos[$tipo] ?? 'DOC1',
                'correlativo_actual' => 0,
                'longitud_correlativo' => 8,
                'activo' => true,
                'empresa_id' => $empresaId,
                'sucursal_id' => $sucursalId
            ]);
        }

        $numero = $serieModel->obtenerSiguienteNumero();
        
        return [
            'serie_id' => $serieModel->id,
            'serie' => $serieModel->serie,
            'numero' => $numero
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pago) {
            if (!$pago->serie || !$pago->numero) {
                try {
                    if (class_exists('\App\Models\Serie')) {
                        $numeracion = self::generarNumero(
                            $pago->tipo_pago,
                            $pago->empresa_id,
                            $pago->sucursal_id,
                            $pago->serie_id
                        );
                        $pago->serie_id = $numeracion['serie_id'];
                        $pago->serie = $numeracion['serie'];
                        $pago->numero = $numeracion['numero'];
                    } else {
                        // Fallback si no existe la clase Serie
                        $pago->serie = 'R001';
                        $pago->numero = 1;
                    }
                } catch (\Exception $e) {
                    // Fallback en caso de error
                    $pago->serie = 'R001';
                    $pago->numero = 1;
                }
            }
        });

        static::saved(function ($pago) {
            $pago->calcularTotales();
        });
    }

    public function calcularTotales()
    {
        $subtotal = $this->detalles()->sum('subtotal');
        $total = $subtotal - $this->descuento;
        
        // Calcular total en bolívares si hay tasa de cambio
        $totalBolivares = null;
        if ($this->tasa_cambio) {
            $totalBolivares = $total * $this->tasa_cambio;
        }

        $this->updateQuietly([
            'subtotal' => $subtotal,
            'total' => $total,
            'total_bolivares' => $totalBolivares
        ]);
    }
}