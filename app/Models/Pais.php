<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Pais extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'pais';

    protected $fillable = [
        'nombre',
        'codigo_iso2',
        'codigo_iso3',
        'codigo_telefonico',
        'moneda_principal',
        'idioma_principal',
        'continente',
        'latitud',
        'longitud',
        'zona_horaria',
        'formato_fecha',
        'formato_moneda',
        'impuesto_predeterminado',
        'separador_miles',
        'separador_decimales',
        'decimales_moneda',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'impuesto_predeterminado' => 'decimal:2',
        'decimales_moneda' => 'integer'
    ];

    public function empresas()
    {
        return $this->hasMany(Empresa::class, 'pais_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'nombre',
                'codigo_iso2',
                'codigo_iso3',
                'codigo_telefonico',
                'moneda_principal',
                'idioma_principal',
                'continente',
                'activo'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Formatear un monto según la configuración del país
     */
    public function formatearMonto($monto)
    {
        return number_format(
            $monto,
            $this->decimales_moneda,
            $this->separador_decimales,
            $this->separador_miles
        );
    }

    /**
     * Obtener el símbolo de la moneda principal
     */
    public function getSimboloMonedaAttribute()
    {
        return match($this->moneda_principal) {
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'VES' => '$',
            'COP' => '$',
            'ARS' => '$',
            'BRL' => 'R$',
            'MXN' => '$',
            'PEN' => 'S/.',
            'PYG' => '₲',
            'UYU' => '$U',
            'DOP' => 'RD$',
            'NIO' => 'C$',
            'HNL' => 'L',
            'PAB' => 'B/.',
            'CRC' => '₡',
            'GTQ' => 'Q',
            'BOB' => 'Bs.',
            'CLP' => '$',
            'CUP' => '$',
            'CLF' => 'UF',
            default => '$'
        };
    }

    /**
     * Obtener las coordenadas de la capital como array
     */
    public function getCoordenadasAttribute()
    {
        if ($this->latitud && $this->longitud) {
            return [
                'lat' => (float) $this->latitud,
                'lng' => (float) $this->longitud
            ];
        }
        return null;
    }

    /**
     * Generar URL de Google Maps para la capital
     */
    public function getGoogleMapsUrlAttribute()
    {
        if ($this->latitud && $this->longitud) {
            return "https://www.google.com/maps?q={$this->latitud},{$this->longitud}";
        }
        return null;
    }

    /**
     * Verificar si el país tiene coordenadas registradas
     */
    public function tieneCoordenadas(): bool
    {
        return !is_null($this->latitud) && !is_null($this->longitud);
    }
}