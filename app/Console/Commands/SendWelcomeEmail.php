<?php

namespace App\Console\Commands;

use App\Mail\PatientWelcomeMail;
use App\Mail\UserCredentialsMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmail extends Command
{
    protected $signature = 'mail:send-welcome {email : Correo del usuario}';

    protected $description = 'Reenvía el correo de bienvenida a un usuario (paciente o staff)';

    public function handle(): int
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("Usuario no encontrado: {$email}");
            return Command::FAILURE;
        }

        $isPatient = $user->roles()->where('slug', 'paciente')->exists();

        $clinicName = DB::table('system_settings')
            ->where('key', 'clinic_name')
            ->value('value') ?? 'FisioVida';

        $token    = app('auth.password.broker')->createToken($user);
        $resetUrl = url('/reset-password/' . $token . '?email=' . urlencode($user->email));

        try {
            if ($isPatient) {
                Mail::to($user->email)->send(new PatientWelcomeMail(
                    user: $user,
                    portalUrl: url('/mi-portal'),
                    resetUrl: $resetUrl,
                    clinicName: $clinicName,
                ));
                $this->info("PatientWelcomeMail enviado a {$email}");
            } else {
                Mail::to($user->email)->send(new UserCredentialsMail(
                    user: $user,
                    loginUrl: url('/login'),
                    resetUrl: $resetUrl,
                ));
                $this->info("UserCredentialsMail enviado a {$email}");
            }
        } catch (\Throwable $e) {
            $this->error('Falló el envío: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
