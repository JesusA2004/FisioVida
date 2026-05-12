<?php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FisioVida\Concerns\CrudHelpers;
use App\Http\Requests\Pagos\PagoStoreRequest;
use App\Http\Requests\Pagos\PagoUpdateRequest;
use App\Http\Resources\PagoResource;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PagosController extends Controller
{
    use CrudHelpers;

    public function index(Request $request)
    {
        $q = $this->like($request->string('q'));
        $status = $request->string('status')->toString();
        $paymentMethod = $request->string('payment_method')->toString();
        $patientPersonaId = $request->integer('patient_persona_id') ?: null;
        $dateFrom = $request->string('date_from')->toString();
        $dateTo = $request->string('date_to')->toString();

        $query = DB::table('payments as p')
            ->leftJoin('personas as pat', 'pat.id', '=', 'p.patient_persona_id')
            ->leftJoin('users as u', 'u.id', '=', 'p.created_by')
            ->select([
                'p.id',
                'p.patient_persona_id',
                'p.concept',
                'p.payment_method',
                'p.amount',
                'p.currency',
                'p.status',
                'p.paid_at',
                'p.reference',
                'p.notes',
                'p.created_by',
                'p.cancelled_at',
                'p.cancelled_by',
                'p.created_at',
                'p.updated_at',
                DB::raw("NULLIF(TRIM(CONCAT_WS(' ', pat.nombres, pat.apellido_paterno, pat.apellido_materno)), '') as patient_name"),
                'u.name as created_by_name',
            ])
            ->orderByDesc('p.id');

        if ($status !== '') {
            $query->where('p.status', $status);
        }

        if ($paymentMethod !== '') {
            $query->where('p.payment_method', $paymentMethod);
        }

        if ($patientPersonaId) {
            $query->where('p.patient_persona_id', $patientPersonaId);
        }

        if ($dateFrom !== '') {
            $query->whereDate('p.created_at', '>=', $dateFrom);
        }

        if ($dateTo !== '') {
            $query->whereDate('p.created_at', '<=', $dateTo);
        }

        if ($q) {
            $query->where(function ($w) use ($q) {
                $w->where('p.concept', 'like', $q)
                    ->orWhere('p.reference', 'like', $q)
                    ->orWhere('p.notes', 'like', $q)
                    ->orWhere('pat.nombres', 'like', $q)
                    ->orWhere('pat.apellido_paterno', 'like', $q)
                    ->orWhere('u.name', 'like', $q);
            });
        }

        $paginator = $query->paginate(15)->withQueryString();

        $summary = $this->buildSummary($patientPersonaId, $dateFrom, $dateTo);

        $patients = DB::table('personas')
            ->whereNull('deleted_at')
            ->whereIn('tipo', ['paciente', 'ambos'])
            ->where('status', 'active')
            ->orderBy('apellido_paterno')
            ->limit(300)
            ->get([
                'id',
                DB::raw("TRIM(CONCAT_WS(' ', nombres, apellido_paterno, apellido_materno)) as label"),
            ]);

        return Inertia::render('Pagos/Index', [
            'rows' => PagoResource::collection(collect($paginator->items()))->resolve(),
            'page' => $this->packPaginator($paginator),
            'filters' => $this->filters($request, ['q', 'status', 'payment_method', 'patient_persona_id', 'date_from', 'date_to']),
            'summary' => $summary,
            'lookups' => [
                'patients' => $patients,
            ],
        ]);
    }

    public function store(PagoStoreRequest $request)
    {
        $payload = $request->validated();
        $payload['provider'] = 'manual';
        $payload['currency'] = strtoupper((string) ($payload['currency'] ?? 'MXN'));
        $payload['created_by'] = $request->user()?->id;
        $payload['created_at'] = now();
        $payload['updated_at'] = now();

        if (empty($payload['paid_at']) && ($payload['status'] ?? '') === 'paid') {
            $payload['paid_at'] = now();
        }

        if (empty($payload['reference'])) {
            $payload['reference'] = 'PAGO-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        }

        DB::table('payments')->insert($payload);
        $id = (int) DB::getPdo()->lastInsertId();

        app(AuditLogService::class)->created(
            $request,
            'Pagos',
            'payment',
            $id,
            'El usuario ' . $request->user()?->name . ' registró un pago por $' . number_format((float) ($payload['amount'] ?? 0), 2) . ' ' . ($payload['currency'] ?? 'MXN') . '.',
            array_diff_key($payload, array_flip(['provider'])),
        );

        return back()->with('success', 'Pago registrado.');
    }

    public function update(PagoUpdateRequest $request, string $id)
    {
        $old = (array) DB::table('payments')->where('id', $id)->first();
        $payload = $request->validated();
        $payload['currency'] = strtoupper((string) ($payload['currency'] ?? 'MXN'));
        $payload['updated_at'] = now();

        DB::table('payments')->where('id', $id)->update($payload);

        $from = (string) ($old['status'] ?? '');
        $to = (string) ($payload['status'] ?? $from);

        if ($from !== '' && $to !== '' && $from !== $to) {
            app(AuditLogService::class)->statusChanged(
                $request,
                'Pagos',
                'payment',
                (int) $id,
                'El usuario ' . $request->user()?->name . ' cambió el estado de pago de ' . $from . ' a ' . $to . '.',
                $from,
                $to,
            );
        } else {
            app(AuditLogService::class)->updated(
                $request,
                'Pagos',
                'payment',
                (int) $id,
                'El usuario ' . $request->user()?->name . ' editó un pago.',
                $old,
                $payload,
            );
        }

        return back()->with('success', 'Pago actualizado.');
    }

    public function cancel(Request $request, string $id)
    {
        $row = DB::table('payments')->where('id', $id)->first();

        if (! $row) {
            return back()->with('warning', 'El pago no existe.');
        }

        if ($row->status === 'cancelled') {
            return back()->with('warning', 'El pago ya estaba cancelado.');
        }

        DB::table('payments')->where('id', $id)->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => $request->user()?->id,
            'updated_at' => now(),
        ]);

        app(AuditLogService::class)->cancelled(
            $request,
            'Pagos',
            'payment',
            (int) $id,
            'El usuario ' . $request->user()?->name . ' canceló el pago #' . $id . '.',
        );

        return back()->with('success', 'Pago cancelado.');
    }

    public function destroy(Request $request, string $id)
    {
        $row = DB::table('payments')->where('id', $id)->first();

        if (! $row) {
            return back()->with('success', 'El pago ya no existe.');
        }

        DB::table('payments')->where('id', $id)->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => $request->user()?->id,
            'updated_at' => now(),
        ]);

        app(AuditLogService::class)->cancelled(
            $request,
            'Pagos',
            'payment',
            (int) $id,
            'El usuario ' . $request->user()?->name . ' canceló el pago #' . $id . '.',
        );

        return back()->with('success', 'Pago cancelado.');
    }

    private function buildSummary(?int $patientPersonaId, string $dateFrom, string $dateTo): array
    {
        $q = DB::table('payments');

        if ($patientPersonaId) {
            $q->where('patient_persona_id', $patientPersonaId);
        }

        if ($dateFrom !== '') {
            $q->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo !== '') {
            $q->whereDate('created_at', '<=', $dateTo);
        }

        $byStatus = (clone $q)
            ->select('status', DB::raw('COUNT(*) as total'), DB::raw('COALESCE(SUM(amount), 0) as amount'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return [
            'total_count' => (clone $q)->count(),
            'paid_amount' => (float) ($byStatus->get('paid')?->amount ?? 0),
            'pending_amount' => (float) ($byStatus->get('pending')?->amount ?? 0),
            'cancelled_count' => (int) ($byStatus->get('cancelled')?->total ?? 0),
        ];
    }
}
