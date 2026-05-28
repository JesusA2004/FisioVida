<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AppointmentRequestApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $data) {}

    public function build(): self
    {
        return $this
            ->subject('¡Tu cita fue confirmada! — FisioVida')
            ->view('emails.appointment_requests.approved')
            ->with([
                ...$this->data,
                'date'      => Carbon::parse($this->data['start_at'])->locale('es')->translatedFormat('d \d\e F \d\e Y'),
                'startTime' => Carbon::parse($this->data['start_at'])->format('h:i a'),
                'endTime'   => Carbon::parse($this->data['end_at'])->format('h:i a'),
            ]);
    }
}
