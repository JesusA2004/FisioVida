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

        $query = DB::table('payments as p')
            ->select(['p.id', 'p.provider', 'p.provider_payment_id', 'p.amount', 'p.currency', 'p.status', 'p.paid_at', 'p.reference', 'p.notes', 'p.created_at', 'p.updated_at'])
            ->orderByDesc('p.id');

        if ($status !== '') {
            $query->where('p.status', $status);
        }

        if ($q) {
            $query->where(function ($w) use ($q) {
                $w->where('p.provider', 'like', $q)
                    ->orWhere('p.reference', 'like', $q)
                    ->orWhere('p.provider_payment_id', 'like', $q);
            });
        }

        $paginator = $query->paginate(10)->withQueryString();

        return Inertia::render('Pagos/Index', [
            'rows' => PagoResource::collection(collect($paginator->items()))->resolve(),
            'page' => $this->packPaginator($paginator),
            'filters' => $this->filters($request, ['q', 'status']),
        ]);
    }

    public function store(PagoStoreRequest $request)
    {
        $payload = $request->validated();
        $payload['currency'] = strtoupper((string) $payload['currency']);
        $payload['created_at'] = now();
        $payload['updated_at'] = now();

        DB::table('payments')->insert($payload);
        $id = (int) DB::getPdo()->lastInsertId();
        app(AuditLogService::class)->created(
            $request,
            'Pagos',
            'payment',
            $id,
            'El usuario '.$request->user()?->name.' registró un pago por $'.number_format((float) ($payload['amount'] ?? 0), 2).' '.($payload['currency'] ?? 'MXN').'.',
            $payload,
        );

        return back()->with('success', 'Pago creado.');
    }

    public function update(PagoUpdateRequest $request, string $id)
    {
        $old = (array) DB::table('payments')->where('id', $id)->first();
        $payload = $request->validated();
        $payload['currency'] = strtoupper((string) $payload['currency']);
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
                'El usuario '.$request->user()?->name.' cambió el estado de pago de '.$from.' a '.$to.'.',
                $from,
                $to,
            );
        } else {
            app(AuditLogService::class)->updated(
                $request,
                'Pagos',
                'payment',
                (int) $id,
                'El usuario '.$request->user()?->name.' editó un pago.',
                $old,
                $payload,
            );
        }

        return back()->with('success', 'Pago actualizado.');
    }

    public function destroy(Request $request, string $id)
    {
        $old = (array) DB::table('payments')->where('id', $id)->first();
        DB::table('payments')->where('id', $id)->delete();
        app(AuditLogService::class)->deleted(
            $request,
            'Pagos',
            'payment',
            (int) $id,
            'El usuario '.$request->user()?->name.' eliminó un pago.',
            $old,
        );

        return back()->with('success', 'Pago eliminado.');
    }
}
