<?php
// app/Http/Controllers/FisioVida/Concerns/CrudHelpers.php

namespace App\Http\Controllers\FisioVida\Concerns;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

trait CrudHelpers {

    protected function filters(Request $request, array $keys): array {
        $out = [];
        foreach ($keys as $k) $out[$k] = $request->input($k);
        return $out;
    }

    protected function packPaginator(LengthAwarePaginator $p): array {
        return [
            'from' => $p->firstItem(),
            'to' => $p->lastItem(),
            'total' => $p->total(),
            'prev' => $p->previousPageUrl(),
            'next' => $p->nextPageUrl(),
        ];
    }

    protected function like(?string $q): ?string {
        $q = trim((string)$q);
        if ($q === '') return null;
        return '%'.str_replace(['%','_'], ['\%','\_'], $q).'%';
    }

}
