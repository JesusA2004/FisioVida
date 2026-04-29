<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AppointmentScheduledMail extends Mailable {

    use Queueable, SerializesModels;

    public function __construct(
        public array $appointment,
    ) {}

    public function build(): self {
        return $this
            ->subject('Cita agendada en FisioVida')
            ->view('emails.appointments.scheduled')
            ->with([
                'appointment' => $this->appointment,
                'date' => Carbon::parse($this->appointment['start_at'])
                    ->locale('es')
                    ->translatedFormat('d \d\e F \d\e Y'),
                'startTime' => Carbon::parse($this->appointment['start_at'])
                    ->format('h:i a'),
                'endTime' => Carbon::parse($this->appointment['end_at'])
                    ->format('h:i a'),
            ]);
    }

}
