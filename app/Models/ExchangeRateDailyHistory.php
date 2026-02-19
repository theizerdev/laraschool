<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRateDailyHistory extends Model
{
    protected $fillable = [
        'monthly_history_id',
        'date',
        'usd_rate',
        'eur_rate',
        'source',
        'fetch_time',
        'recorded_at',
        'recorded_by',
    ];

    protected $casts = [
        'usd_rate' => 'decimal:4',
        'eur_rate' => 'decimal:4',
        'recorded_at' => 'datetime',
    ];

    public function monthlyHistory()
    {
        return $this->belongsTo(ExchangeRateMonthlyHistory::class, 'monthly_history_id');
    }
}
