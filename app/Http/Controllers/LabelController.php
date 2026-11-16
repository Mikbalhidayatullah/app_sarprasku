<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterRequest;
use App\Models\School;
use App\Services\AssetQuery;
use App\Services\LabelService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class LabelController extends Controller
{
    public function __invoke(FilterRequest $r, LabelService $service)
    {
        Gate::authorize('labels.print');
        $filters = $r->validated();
        $q = AssetQuery::build($filters)->with('classification');
        if ($filters['ids'] ?? null) {
            $q->whereIn('id', $filters['ids']);
        }
        $assets = $q->orderBy('inventory_number')->limit(101)->get();
        if ($assets->count() > 100) {
            throw ValidationException::withMessages(['ids' => 'Maksimal 100 label per cetak; persempit filter atau pilihan.']);
        }
        if ($filters['ids'] ?? null) {
            abort_unless($assets->count() === count($filters['ids']), 403);
        }
        $school = School::firstOrFail();
        foreach ($assets as $a) {
            Gate::authorize('print', $a);
            $chars = max(10, (int) floor(($school->label_width_mm - 38) / 1.3));
            $lines = (int) ceil(mb_strlen('Kode: '.($a->classification?->code ?? 'Belum diverifikasi')) / $chars) + (int) ceil(mb_strlen($a->inventory_number) / $chars);
            if ($a->official_register) {
                $lines += (int) ceil(mb_strlen('NUP: '.$a->official_register) / $chars);
            }
            if (20 + $lines * 3 > $school->label_height_mm) {
                throw ValidationException::withMessages(['ids' => 'Identitas '.$a->inventory_number.' terlalu panjang untuk ukuran label ini. Perbesar ukuran label agar identitas utuh dan QR tidak tertimpa.']);
            }
        }
        $data = ['assets' => $assets, 'school' => School::firstOrFail(), 'qr' => $assets->mapWithKeys(fn ($a) => [$a->id => $service->qr($a)])->all(), 'filters' => $filters];
        if (($filters['format'] ?? '') === 'pdf') {
            return Pdf::loadView('labels.sheet', $data)->setPaper('a4')->setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false, 'defaultMediaType' => 'print'])->download('label-aset.pdf');
        }

        return view('labels.preview', $data);
    }
}
