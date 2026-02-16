<?php
// app/Http/Controllers/FisioVida/PagosController.php

namespace App\Http\Controllers\FisioVida;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FisioVida\Concerns\CrudHelpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PagosController extends Controller {

    use CrudHelpers;

    public function index(Request $request) {
        $q = $this->like($request->string('q'));
        $status = $request->string('status')->toString();
        $query = DB::table('payments as p')
            ->select(['p.id','p.provider','p.provider_payment_id','p.amount','p.currency','p.status','p.paid_at','p.reference','p.notes','p.created_at','p.updated_at'])
            ->orderByDesc('p.id');
        if ($status !== '') $query->where('p.status', $status);
        if ($q) {
            $query->where(function($w) use ($q){
                $w->where('p.provider','like',$q)
                    ->orWhere('p.reference','like',$q)
                    ->orWhere('p.provider_payment_id','like',$q);
            });
        }
        $p = $query->paginate(10)->withQueryString();
        $rows = collect($p->items())->values();
        return Inertia::render('Pagos/Index', [
            'rows' => $rows,
            'page' => $this->packPaginator($p),
            'filters' => $this->filters($request, ['q','status']),
        ]);
    }

    public function store(Request $request) {
        $data = $request->validate([
            'provider' => ['required','string','max:50'],
            'provider_payment_id' => ['nullable','string','max:120'],
            'amount' => ['required','numeric','min:0'],
            'currency' => ['required','string','max:10'],
            'status' => ['required','in:pending,paid,failed,refunded'],
            'paid_at' => ['nullable','date'],
            'reference' => ['nullable','string','max:120'],
            'notes' => ['nullable','string','max:255'],
        ]);
        $data['created_at'] = now();
        $data['updated_at'] = now();
        DB::table('payments')->insert($data);
        return back()->with('success', 'Pago creado.');
    }

    public function update(Request $request, string $id) {
        $data = $request->validate([
            'provider' => ['required','string','max:50'],
            'provider_payment_id' => ['nullable','string','max:120'],
            'amount' => ['required','numeric','min:0'],
            'currency' => ['required','string','max:10'],
            'status' => ['required','in:pending,paid,failed,refunded'],
            'paid_at' => ['nullable','date'],
            'reference' => ['nullable','string','max:120'],
            'notes' => ['nullable','string','max:255'],
        ]);
        $data['updated_at'] = now();
        DB::table('payments')->where('id',$id)->update($data);
        return back()->with('success', 'Pago actualizado.');
    }

    public function destroy(string $id) {
        DB::table('payments')->where('id',$id)->delete();
        return back()->with('success', 'Pago eliminado.');
    }

}
