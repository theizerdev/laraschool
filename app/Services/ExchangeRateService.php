<?php

namespace App\Services;

use App\Models\ExchangeRate;
use App\Models\ExchangeRateMonthlyHistory;
use App\Models\ExchangeRateDailyHistory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ExchangeRateService
{
    private const DOLARVZLA_API = 'https://api.dolarvzla.com/public/exchange-rate';
    private const BACKUP_API = 'https://api.exchangerate-api.com/v4/latest/USD';

    public function fetchAndStoreRates(): bool
    {
        try {
            $rates = $this->fetchFromDolarVzla() ?? $this->fetchFromBackupAPI();

            if ($rates) {
                return $this->storeRates($rates);
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Error fetching exchange rates: ' . $e->getMessage());
            return false;
        }
    }

    public function fetchHistoricalBCV(string $from, string $to): ?array
    {
        try {
            $base = config('services.dolarvzla.base_url', 'https://api.dolarvzla.com/public');
            $key = config('services.dolarvzla.key');
            $headers = [];
            if (!empty($key)) {
                $headers['x-dolarvzla-key'] = $key;
            }
            $url = rtrim($base, '/') . '/bcv/exchange-rate/list';
            $response = Http::timeout(20)
                ->withHeaders($headers)
                ->get($url, [
                    'from' => $from,
                    'to' => $to,
                ]);
            if ($response->successful()) {
                $data = $response->json();
                $rates = $data['rates'] ?? null;
                if (is_array($rates)) {
                    return $rates;
                }
            } else {
                Log::warning('DolarVzla historical API failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('fetchHistoricalBCV error: ' . $e->getMessage());
        }
        return null;
    }

    public function backfillMonthBCV(int $year, int $month): int
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        $list = $this->fetchHistoricalBCV($startDate->toDateString(), $endDate->toDateString());
        if (!$list) {
            return 0;
        }
        $byDate = [];
        foreach ($list as $item) {
            if (!isset($item['date'])) {
                continue;
            }
            $d = Carbon::parse($item['date'])->toDateString();
            $byDate[$d] = $item;
        }
        $prevStart = $startDate->copy()->subMonth()->startOfMonth()->toDateString();
        $prevEnd = $startDate->copy()->subMonth()->endOfMonth()->toDateString();
        $prevList = $this->fetchHistoricalBCV($prevStart, $prevEnd) ?? [];
        $prevLast = null;
        if (!empty($prevList)) {
            usort($prevList, function ($a, $b) {
                return strcmp($a['date'], $b['date']);
            });
            $prevLast = end($prevList);
            if ($prevLast && isset($prevLast['usd'])) {
                $prevLast = [
                    'usd' => (float)$prevLast['usd'],
                    'eur' => array_key_exists('eur', $prevLast) && $prevLast['eur'] !== null ? (float)$prevLast['eur'] : null,
                    'date' => Carbon::parse($prevLast['date'])->toDateString(),
                ];
            } else {
                $prevLast = null;
            }
        }
        $lastKnownUsd = $prevLast['usd'] ?? null;
        $lastKnownEur = $prevLast['eur'] ?? null;
        $lastKnownDate = $prevLast['date'] ?? null;
        $count = 0;
        $period = new \Carbon\CarbonPeriod($startDate, $endDate);
        foreach ($period as $day) {
            $dateStr = $day->toDateString();
            if (isset($byDate[$dateStr]) && isset($byDate[$dateStr]['usd'])) {
                $usd = (float)$byDate[$dateStr]['usd'];
                $eur = array_key_exists('eur', $byDate[$dateStr]) && $byDate[$dateStr]['eur'] !== null ? (float)$byDate[$dateStr]['eur'] : null;
                ExchangeRate::updateOrCreate(
                    ['date' => $dateStr, 'fetch_time' => '10:00:00'],
                    [
                        'usd_rate' => $usd,
                        'eur_rate' => $eur,
                        'source' => 'bcv',
                        'raw_data' => $byDate[$dateStr],
                    ]
                );
                $lastKnownUsd = $usd;
                $lastKnownEur = $eur;
                $lastKnownDate = $dateStr;
                $count++;
            } else {
                if ($lastKnownUsd !== null) {
                    ExchangeRate::updateOrCreate(
                        ['date' => $dateStr, 'fetch_time' => '10:00:00'],
                        [
                            'usd_rate' => $lastKnownUsd,
                            'eur_rate' => $lastKnownEur,
                            'source' => 'bcv',
                            'raw_data' => [
                                'filled' => true,
                                'filled_ref_date' => $lastKnownDate,
                            ],
                        ]
                    );
                    $count++;
                }
            }
        }
        return $count;
    }

    private function fetchFromDolarVzla(): ?array
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 15,
                    'method' => 'GET',
                    'header' => 'User-Agent: Mozilla/5.0'
                ]
            ]);

            $response = file_get_contents(self::DOLARVZLA_API, false, $context);

            if ($response !== false) {
                $data = json_decode($response, true);

                if (isset($data['current']['usd'])) {
                    $usdRate = (float) $data['current']['usd'];
                    $eurRate = (float) $data['current']['eur'];

                    Log::info('DolarVzla rates fetched successfully', ['usd' => $usdRate, 'eur' => $eurRate]);

                    return [
                        'usd_rate' => $usdRate,
                        'eur_rate' => $eurRate,
                        'source' => 'dolarvzla'
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('DolarVzla API fetch failed: ' . $e->getMessage());
        }

        return null;
    }

    private function fetchFromBackupAPI(): ?array
    {
        try {
            $response = Http::timeout(15)->get(self::BACKUP_API);

            if ($response->successful()) {
                $data = $response->json();

                $vesRate = $data['rates']['VES'] ?? null;
                $eurRate = $data['rates']['EUR'] ?? null;

                if ($vesRate) {
                    return [
                        'usd_rate' => $vesRate,
                        'eur_rate' => $eurRate ? $vesRate / $eurRate : null,
                        'source' => 'backup_api'
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Backup API fetch failed: ' . $e->getMessage());
        }

        return null;
    }

    public function ensureMonthlyHistory(int $year, int $month): ?\App\Models\ExchangeRateMonthlyHistory
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = Carbon::create($year, $month, 1)->endOfMonth();
        $this->backfillMonthBCV((int)$start->year, (int)$start->month);
        $rates = ExchangeRate::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->get();
        $count = $rates->count();
        $usdAvg = round((float) $rates->avg('usd_rate'), 4);
        $usdMin = round((float) $rates->min('usd_rate'), 4);
        $usdMax = round((float) $rates->max('usd_rate'), 4);
        $eurCollection = $rates->pluck('eur_rate')->filter(fn($v) => $v !== null);
        $eurAvg = $eurCollection->isNotEmpty() ? round((float) $eurCollection->avg(), 4) : null;
        $eurMin = $eurCollection->isNotEmpty() ? round((float) $eurCollection->min(), 4) : null;
        $eurMax = $eurCollection->isNotEmpty() ? round((float) $eurCollection->max(), 4) : null;
        $sources = $rates->pluck('source')->unique()->values()->all();
        $period = new \Carbon\CarbonPeriod($start, $end);
        $dailyRecords = [];
        foreach ($period as $day) {
            $found = $rates->first(function ($r) use ($day) {
                return $r->date instanceof Carbon ? $r->date->isSameDay($day) : Carbon::parse($r->date)->isSameDay($day);
            });
            $dailyRecords[] = [
                'date' => $day->toDateString(),
                'usd_rate' => $found ? (float)$found->usd_rate : null,
                'eur_rate' => $found && $found->eur_rate !== null ? (float)$found->eur_rate : null,
                'source' => $found ? $found->source : null,
                'fetch_time' => $found && $found->fetch_time ? ($found->fetch_time instanceof Carbon ? $found->fetch_time->format('H:i:s') : (string)$found->fetch_time) : null,
            ];
        }
        $monthly = ExchangeRateMonthlyHistory::updateOrCreate(
            ['year' => (int)$start->year, 'month' => (int)$start->month],
            [
                'usd_avg' => $usdAvg,
                'usd_min' => $usdMin,
                'usd_max' => $usdMax,
                'eur_avg' => $eurAvg,
                'eur_min' => $eurMin,
                'eur_max' => $eurMax,
                'records_count' => $count,
                'sources' => $sources,
                'daily_records' => $dailyRecords,
                'generated_at' => now(),
                'generated_by' => auth()->id(),
            ]
        );
        foreach ($rates as $rate) {
            ExchangeRateDailyHistory::updateOrCreate(
                [
                    'monthly_history_id' => $monthly->id,
                    'date' => $rate->date,
                ],
                [
                    'usd_rate' => $rate->usd_rate,
                    'eur_rate' => $rate->eur_rate,
                    'source' => $rate->source,
                    'fetch_time' => $rate->fetch_time,
                    'recorded_at' => now(),
                    'recorded_by' => auth()->id(),
                ]
            );
        }
        return $monthly;
    }

    public function getUsdRateForDate(Carbon $date): ?float
    {
        $hist = ExchangeRateDailyHistory::whereDate('date', $date->toDateString())->first();
        if ($hist && $hist->usd_rate) {
            return (float)$hist->usd_rate;
        }
        $this->ensureMonthlyHistory((int)$date->year, (int)$date->month);
        $hist = ExchangeRateDailyHistory::whereDate('date', $date->toDateString())->first();
        if ($hist && $hist->usd_rate) {
            return (float)$hist->usd_rate;
        }
        $rate = ExchangeRate::whereDate('date', $date->toDateString())->first();
        return $rate ? (float)$rate->usd_rate : null;
    }
    private function storeRates(array $rates): bool
    {
        try {
            // Actualizar o crear la tasa del día (solo una por día)
            ExchangeRate::updateOrCreate(
                ['date' => today()],
                [
                    'usd_rate' => $rates['usd_rate'],
                    'eur_rate' => $rates['eur_rate'],
                    'source' => $rates['source'],
                    'fetch_time' => now()->format('H:i:s'),
                    'raw_data' => $rates
                ]
            );

            Log::info('Exchange rates stored successfully', $rates);
            return true;
        } catch (\Exception $e) {
            Log::error('Error storing exchange rates: ' . $e->getMessage());
            return false;
        }
    }

    public function getLatestRate(string $currency = 'USD'): ?float
    {
        return ExchangeRate::getLatestRate($currency);
    }

    public function getTodayRates()
    {
        return ExchangeRate::getTodayRates();
    }
}
