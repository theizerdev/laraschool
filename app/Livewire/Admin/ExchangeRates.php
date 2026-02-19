<?php

namespace App\Livewire\Admin;

use App\Traits\HasDynamicLayout;
use Livewire\Component;
use App\Models\ExchangeRate;
use App\Models\ExchangeRateMonthlyHistory;
use App\Models\ExchangeRateDailyHistory;
use App\Services\ExchangeRateService;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class ExchangeRates extends Component
{
    use HasDynamicLayout;

    public $lastUpdate;
    public $showEditModal = false;
    public $editingRate;
    public $selectedMonth;
    public $showMonthHistory = false;
    public $monthRates = [];
    public $monthStats = [];
    public $monthHistoryExists = false;
    public $monthHistoryInfo = [];
    public $isBackfilling = false;

    #[Validate('required|numeric|min:0.0001|max:999999.9999')]
    public $usd_rate;

    #[Validate('required|numeric|min:0.0001|max:999999.9999')]
    public $eur_rate;

    #[Validate('required|string|max:255')]
    public $edit_reason;

    public function mount()
    {
        abort_unless(auth()->user()->can('view exchange-rates'), 403);
        $this->lastUpdate = now()->format('H:i:s');
        $this->selectedMonth = now()->format('Y-m');
        $this->refreshMonthHistoryStatus();
    }

    #[On('refresh-rates')]
    public function refreshData()
    {
        $this->lastUpdate = now()->format('H:i:s');
    }

    public function fetchNow()
    {
        try {
            $service = new ExchangeRateService();
            $success = $service->fetchAndStoreRates();

            if ($success) {
                $todayRate = ExchangeRate::getTodayRate();
                session()->flash('success', "Tasa actualizada: USD = {$todayRate->usd_rate} Bs. (Fuente: {$todayRate->source})");
            } else {
                session()->flash('error', 'No se pudo obtener la tasa. Verifique la conexión a internet.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Error técnico: ' . $e->getMessage());
        }

        $this->refreshData();
    }

    public function updatedSelectedMonth()
    {
        $this->refreshMonthHistoryStatus();
    }

    public function editRate($rateId = null)
    {
        abort_unless(auth()->user()->can('edit exchange-rates'), 403);

        if ($rateId) {
            $this->editingRate = ExchangeRate::findOrFail($rateId);
        } else {
            $this->editingRate = ExchangeRate::getTodayRate();
        }

        if ($this->editingRate) {
            $this->usd_rate = $this->editingRate->usd_rate;
            $this->eur_rate = $this->editingRate->eur_rate;
        }

        $this->edit_reason = '';
        $this->showEditModal = true;
    }

    public function saveRate()
    {
        abort_unless(auth()->user()->can('edit exchange-rates'), 403);

        $this->validate();

        if ($this->editingRate) {
            // Update existing rate
            $oldUsd = $this->editingRate->usd_rate;
            $oldEur = $this->editingRate->eur_rate;

            // Merge with existing raw_data to preserve history
            $newRawData = array_merge(
                $this->editingRate->raw_data ?? [],
                [
                    'edited_by' => auth()->user()->name,
                    'edit_reason' => $this->edit_reason,
                    'previous_usd' => $oldUsd,
                    'previous_eur' => $oldEur,
                    'edited_at' => now()->toISOString()
                ]
            );

            $this->editingRate->update([
                'usd_rate' => $this->usd_rate,
                'eur_rate' => $this->eur_rate,
                'source' => 'Modificado',
                'raw_data' => $newRawData
            ]);
        } else {
            // Create new rate for today
            ExchangeRate::create([
                'date' => today(),
                'usd_rate' => $this->usd_rate,
                'eur_rate' => $this->eur_rate,
                'source' => 'Manual',
                'fetch_time' => now(),
                'raw_data' => [
                    'created_by' => auth()->user()->name,
                    'creation_reason' => $this->edit_reason,
                    'created_at' => now()->toISOString()
                ]
            ]);
        }

        $this->closeEditModal();
        $this->refreshData();

        session()->flash('success', 'Tasa de cambio actualizada correctamente.');
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editingRate = null;
        $this->reset(['usd_rate', 'eur_rate', 'edit_reason']);
        $this->resetValidation();
    }

    public function loadMonthHistory()
    {
        try {
            if (!$this->selectedMonth) {
                session()->flash('error', 'Seleccione un mes válido.');
                return;
            }

            $start = Carbon::createFromFormat('Y-m', $this->selectedMonth)->startOfMonth();
            $end = (clone $start)->endOfMonth();

            $rates = ExchangeRate::whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->orderBy('date')
                ->get();

            $count = $rates->count();
            // Construir la lista completa del mes (incluyendo días sin registro)
            $period = CarbonPeriod::create($start, $end);
            $completeRates = collect();
            foreach ($period as $day) {
                $found = $rates->first(function ($r) use ($day) {
                    return $r->date instanceof Carbon ? $r->date->isSameDay($day) : Carbon::parse($r->date)->isSameDay($day);
                });
                if ($found) {
                    $completeRates->push($found);
                } else {
                    $completeRates->push(new ExchangeRate([
                        'date' => $day,
                        'usd_rate' => null,
                        'eur_rate' => null,
                        'source' => null,
                        'fetch_time' => null,
                    ]));
                }
            }

            $usdAvg = round((float) $rates->avg('usd_rate'), 4);
            $usdMin = round((float) $rates->min('usd_rate'), 4);
            $usdMax = round((float) $rates->max('usd_rate'), 4);

            $eurCollection = $rates->pluck('eur_rate')->filter(fn($v) => $v !== null);
            $eurAvg = $eurCollection->isNotEmpty() ? round((float) $eurCollection->avg(), 4) : null;
            $eurMin = $eurCollection->isNotEmpty() ? round((float) $eurCollection->min(), 4) : null;
            $eurMax = $eurCollection->isNotEmpty() ? round((float) $eurCollection->max(), 4) : null;

            $sources = $rates->pluck('source')->unique()->values()->all();

            ExchangeRateMonthlyHistory::updateOrCreate(
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
                    'generated_at' => now(),
                    'generated_by' => auth()->id(),
                ]
            );

            $monthly = ExchangeRateMonthlyHistory::where('year', $start->year)
                ->where('month', $start->month)
                ->first();

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

            $this->monthRates = $completeRates;
            $this->monthStats = [
                'period' => $start->format('F Y'),
                'usd' => ['avg' => $usdAvg, 'min' => $usdMin, 'max' => $usdMax],
                'eur' => ['avg' => $eurAvg, 'min' => $eurMin, 'max' => $eurMax],
                'records' => $count,
                'sources' => $sources,
            ];
            $this->showMonthHistory = true;
            session()->flash('success', 'Histórico mensual generado y guardado correctamente.');
            $this->refreshMonthHistoryStatus();
        } catch (\Exception $e) {
            $this->showMonthHistory = false;
            session()->flash('error', 'Error al cargar el histórico: ' . $e->getMessage());
        }
    }

    public function saveMonthHistory()
    {
        try {
            if (!$this->selectedMonth) {
                session()->flash('error', 'Seleccione un mes válido.');
                return;
            }
            $start = Carbon::createFromFormat('Y-m', $this->selectedMonth)->startOfMonth();
            $end = (clone $start)->endOfMonth();

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
            // Construir daily_records para todos los días del mes (incluye fechas sin tasa)
            $period = CarbonPeriod::create($start, $end);
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

            ExchangeRateMonthlyHistory::updateOrCreate(
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

            session()->flash('success', 'Tasas del mes guardadas con fechas en el histórico mensual.');
            $this->refreshMonthHistoryStatus();
        } catch (\Exception $e) {
            session()->flash('error', 'Error al guardar el histórico mensual: ' . $e->getMessage());
        }
    }

    private function refreshMonthHistoryStatus()
    {
        try {
            if (!$this->selectedMonth) {
                $this->monthHistoryExists = false;
                $this->monthHistoryInfo = [];
                return;
            }
            $start = Carbon::createFromFormat('Y-m', $this->selectedMonth)->startOfMonth();
            $monthly = ExchangeRateMonthlyHistory::where('year', $start->year)
                ->where('month', $start->month)
                ->first();
            if ($monthly) {
                $daily = is_array($monthly->daily_records) ? $monthly->daily_records : [];
                $this->monthHistoryExists = !empty($daily);
                $this->monthHistoryInfo = [
                    'generated_at' => $monthly->generated_at ? $monthly->generated_at->format('d/m/Y H:i') : null,
                    'records_count' => $monthly->records_count,
                    'daily_count' => is_array($daily) ? count($daily) : 0,
                ];
            } else {
                $this->monthHistoryExists = false;
                $this->monthHistoryInfo = [];
            }
        } catch (\Throwable $e) {
            $this->monthHistoryExists = false;
            $this->monthHistoryInfo = [];
        }
    }

    public function render()
    {
        $todayRate = ExchangeRate::getTodayRate();
        $rates = ExchangeRate::orderBy('created_at', 'desc')->take(7)->get();
        $chartData = ExchangeRate::orderBy('created_at', 'desc')->take(30)->get();

        // Calcular estadísticas para la vista
        $stats = [
            'usd_rate' => $todayRate ? $todayRate->usd_rate : 0,
            'eur_rate' => $todayRate ? $todayRate->eur_rate : 0,
            'date' => $todayRate ? $todayRate->date->format('d/m/Y') : 'N/A',
            'last_fetch' => $todayRate ? $todayRate->fetch_time->format('H:i') : 'N/A',
            'source' => $todayRate ? $todayRate->source : 'N/A'
        ];

        return view('livewire.admin.exchange-rates', [
            'todayRate' => $todayRate,
            'rates' => $rates,
            'chartData' => $chartData,
            'stats' => $stats,
            'selectedMonth' => $this->selectedMonth,
            'showMonthHistory' => $this->showMonthHistory,
            'monthRates' => $this->monthRates,
            'monthStats' => $this->monthStats,
            'monthHistoryExists' => $this->monthHistoryExists,
            'monthHistoryInfo' => $this->monthHistoryInfo,
        ])->layout($this->getLayout());
    }

    public function backfillMonthFromBCV()
    {
        try {
            $this->isBackfilling = true;
            if (!$this->selectedMonth) {
                session()->flash('error', 'Seleccione un mes válido.');
                $this->isBackfilling = false;
                return;
            }
            $start = Carbon::createFromFormat('Y-m', $this->selectedMonth)->startOfMonth();
            $service = new ExchangeRateService();
            $updated = $service->backfillMonthBCV((int)$start->year, (int)$start->month);
            $this->isBackfilling = false;
            if ($updated > 0) {
                session()->flash('success', "Mes rellenado con BCV: {$updated} días actualizados.");
                $this->loadMonthHistory();
            } else {
                session()->flash('error', 'No se pudo obtener datos históricos BCV para este mes.');
            }
        } catch (\Throwable $e) {
            $this->isBackfilling = false;
            session()->flash('error', 'Error al rellenar el mes desde BCV: ' . $e->getMessage());
        }
    }
}
