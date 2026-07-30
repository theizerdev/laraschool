<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Pago;

class PaymentReceived extends Mailable
{
    use Queueable, SerializesModels;

    public $pago;
    public $estudiante;

    public function __construct(Pago $pago)
    {
        $this->pago = $pago;
        $this->estudiante = $pago->matricula->student;
    }

    public function build()
    {
        return $this->subject('Confirmación de Pago Recibido - U.E JOSE MARIA VARGAS')
                    ->view('emails.payment-received');
    }
}