<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterRequest;
use App\Models\School;
use App\Services\ReportData;
use App\Support\Display;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    private function data(FilterRequest $r): array
    {
        Gate::authorize('reports.view');
        $f = $r->validated();
        $type = $f['type'] ?? 'inventory';

        return [$f, $type, ReportData::query($f)];
    }

    public function index(FilterRequest $r)
    {
        [$f,$type,$q] = $this->data($r);

        return view('reports.index', AssetController::options() + ['filters' => $f, 'type' => $type, 'headers' => ReportData::headers($type), 'records' => $q->paginate(25)->withQueryString()]);
    }

    public function export(FilterRequest $r)
    {
        Gate::authorize('reports.export');
        [$f,$type,$q] = $this->data($r);

        return response()->streamDownload(function () use ($type, $q) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ReportData::headers($type), ',', '"', '');
            foreach ($q->lazy(250) as $m) {
                fputcsv($out, array_map([Display::class, 'csv'], ReportData::row($m, $type)), ',', '"', '');
            } fclose($out);
        }, 'laporan-'.$type.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function pdf(FilterRequest $r)
    {
        Gate::authorize('reports.export');
        [$f,$type,$q] = $this->data($r);
        if ((clone $q)->count() > 1000) {
            throw ValidationException::withMessages(['q' => 'PDF dibatasi 1.000 baris. Persepit filter atau gunakan CSV.']);
        }

        return Pdf::loadView('reports.pdf', ['type' => $type, 'headers' => ReportData::headers($type), 'records' => $q->get(), 'filters' => $f, 'school' => School::firstOrFail()])->setPaper('a4', 'landscape')->setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false])->download('laporan-'.$type.'.pdf');
    }
}
