<?php

namespace App\Observers;

use App\Models\Pago;
use App\Models\PaymentSchedule;

class PagoObserver
{
    /**
     * Handle the Pago "created" event.
     * Sincronizar las cuotas cuando se crea un pago aprobado
     */
    public function created(Pago $pago): void
    {
        if ($pago->estado === 'aprobado') {
            $this->syncPaymentSchedules($pago);
        }
    }

    /**
     * Handle the Pago "updated" event.
     * Actualizar sincronización si cambia el estado del pago
     */
    public function updated(Pago $pago): void
    {
        if ($pago->isDirty('estado')) {
            if ($pago->estado === 'aprobado') {
                $this->syncPaymentSchedules($pago);
            } elseif ($pago->getOriginal('estado') === 'aprobado') {
                // Si estaba aprobado y cambió a otro estado, recalcular
                $this->syncPaymentSchedules($pago);
            }
        }

        // Si se actualizan los detalles del pago
        if ($pago->isDirty('detalles')) {
            $this->syncPaymentSchedules($pago);
        }
    }

    /**
     * Handle the Pago "deleted" event.
     * Recalcular montos cuando se elimina un pago
     */
    public function deleted(Pago $pago): void
    {
        $this->syncPaymentSchedules($pago);
    }

    /**
     * Handle the Pago "restored" event.
     */
    public function restored(Pago $pago): void
    {
        if ($pago->estado === 'aprobado') {
            $this->syncPaymentSchedules($pago);
        }
    }

    /**
     * Handle the Pago "force deleted" event.
     */
    public function forceDeleted(Pago $pago): void
    {
        $this->syncPaymentSchedules($pago);
    }

    /**
     * Sincronizar los payment schedules asociados al pago
     */
    private function syncPaymentSchedules(Pago $pago)
    {
        if (!$pago->matricula_id) {
            return;
        }

        // Obtener todos los payment schedules de la matrícula
        $paymentSchedules = PaymentSchedule::where('matricula_id', $pago->matricula_id)
            ->orderBy('numero_cuota')
            ->get();

        foreach ($paymentSchedules as $schedule) {
            $schedule->syncPaidAmountFromPayments();
        }

        // Actualizar la solvencia de la matrícula después de sincronizar las cuotas
        $matricula = $pago->matricula;
        if($matricula) {
            $matricula->updateSolvencia();

            // Recalcular la morosidad para mantener sincronizada la información
            $matricula->updateMorosidad();
        }
    }
}
