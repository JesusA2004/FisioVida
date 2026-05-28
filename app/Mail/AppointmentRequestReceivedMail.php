<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AppointmentRequestReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $data) {}

    public function build(): self
    {
        $dateLabel = $this->data['preferred_date']
            ? Carbon::parse($this->data['preferred_date'])->locale('es')->translatedFormat('d \d\e F \d\e Y')
            : null;

        return $this
            ->subject('Solicitud de cita recibida — FisioVida')
            ->view('emails.appointment_requests.received')
            ->with([...$this->data, 'date_label' => $dateLabel]);
    }
}
