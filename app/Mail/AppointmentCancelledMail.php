<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AppointmentCancelledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $appointment) {}

    public function build(): self
    {
        return $this
            ->subject('Cita cancelada — FisioVida')
            ->view('emails.appointments.cancelled')
            ->with([
                'appointment' => $this->appointment,
                'date'        => Carbon::parse($this->appointment['start_at'])->locale('es')->translatedFormat('d \d\e F \d\e Y'),
                'startTime'   => Carbon::parse($this->appointment['start_at'])->format('h:i a'),
            ]);
    }
}
