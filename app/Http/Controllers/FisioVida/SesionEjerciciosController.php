<?php
// app/Http/Controllers/FisioVida/SesionEjerciciosController.php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SesionEjerciciosController extends Controller {

    public function store(Request $request, string $sessionId) {
        $data = $request->validate([
            'exercise_id' => ['required','integer'],
            'sets' => ['nullable','integer','min:1','max:999'],
            'reps' => ['nullable','integer','min:1','max:999'],
            'seconds' => ['nullable','integer','min:1','max:99999'],
            'notes' => ['nullable','string','max:255'],
        ]);
        DB::table('session_exercises')->updateOrInsert(
            ['session_id' => (int)$sessionId, 'exercise_id' => (int)$data['exercise_id']],
            ['sets' => $data['sets'] ?? null, 'reps' => $data['reps'] ?? null, 'seconds' => $data['seconds'] ?? null, 'notes' => $data['notes'] ?? null]
        );
        return back()->with('success', 'Ejercicio asignado a la sesión.');
    }

    public function update(Request $request, string $sessionId, string $exerciseId) {
        $data = $request->validate([
            'sets' => ['nullable','integer','min:1','max:999'],
            'reps' => ['nullable','integer','min:1','max:999'],
            'seconds' => ['nullable','integer','min:1','max:99999'],
            'notes' => ['nullable','string','max:255'],
        ]);
        DB::table('session_exercises')
            ->where('session_id',(int)$sessionId)
            ->where('exercise_id',(int)$exerciseId)
            ->update($data);
        return back()->with('success', 'Asignación actualizada.');
    }

    public function destroy(string $sessionId, string $exerciseId) {
        DB::table('session_exercises')
            ->where('session_id',(int)$sessionId)
            ->where('exercise_id',(int)$exerciseId)
            ->delete();

        return back()->with('success', 'Ejercicio removido de la sesión.');
    }

}
