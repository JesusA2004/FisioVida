<?php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MiJornadaController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();
        $today  = Carbon::today();

        $appointments = DB::table('appointments as a')
            ->join('personas as p', 'p.id', '=', 'a.patient_persona_id')
            ->join('users as u', 'u.id', '=', 'a.therapist_user_id')
            ->whereNull('p.deleted_at')
            ->where('a.therapist_user_id', $userId)
            ->whereDate('a.start_at', $today)
            ->orderBy('a.start_at')
            ->select([
                'a.id',
                'a.patient_persona_id',
                'a.start_at',
                'a.end_at',
                'a.status',
                'a.notes',
                'u.name as therapist_name',
                DB::raw("TRIM(CONCAT_WS(' ', p.nombres, p.apellido_paterno, p.apellido_materno)) as patient_name"),
            ])
            ->get()
            ->map(function ($appt) {
                $patientId = $appt->patient_persona_id;

                // Última sesión del paciente
                $lastSession = DB::table('therapy_sessions')
                    ->where('patient_persona_id', $patientId)
                    ->orderByDesc('session_date')
                    ->first(['session_date', 'pain_scale', 'assessment', 'plan', 'subjective', 'notes']);

                return [
                    'id'                 => $appt->id,
                    'patient_persona_id' => $patientId,
                    'patient_name'       => $appt->patient_name ?: 'Sin nombre',
                    'therapist_name'     => $appt->therapist_name ?? '',
                    'start_at'           => $appt->start_at,
                    'end_at'             => $appt->end_at,
                    'status'             => $appt->status,
                    'notes'              => $appt->notes,
                    'last_session'       => $lastSession ? [
                        'session_date' => $lastSession->session_date,
                        'pain_scale'   => $lastSession->pain_scale,
                        'assessment'   => $lastSession->assessment,
                        'plan'         => $lastSession->plan,
                        'subjective'   => $lastSession->subjective,
                        'notes'        => $lastSession->notes,
                    ] : null,
                ];
            });

        return Inertia::render('MiJornada/Index', [
            'appointments' => $appointments,
            'today'        => $today->format('Y-m-d'),
            'todayLabel'   => now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY'),
        ]);
    }
}
