<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttachmentRequest;
use App\Models\Asset;
use App\Models\Attachment;
use App\Models\Maintenance;
use App\Models\Transfer;
use App\Services\History;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AttachmentController extends Controller
{
    public function store(AttachmentRequest $r, Asset $asset)
    {
        $file = $r->file('file');
        if (in_array($file->getMimeType(), ['image/jpeg', 'image/png']) && ! @getimagesize($file->getRealPath())) {
            throw ValidationException::withMessages(['file' => 'Berkas gambar tidak valid.']);
        }
        if ($file->getMimeType() === 'application/pdf' && file_get_contents($file->getRealPath(), false, null, 0, 5) !== '%PDF-') {
            throw ValidationException::withMessages(['file' => 'Header PDF tidak valid.']);
        }
        $path = null;
        try {
            DB::transaction(function () use ($r, $asset, $file, &$path) {
                $a = Asset::whereKey($asset->id)->lockForUpdate()->firstOrFail();
                if ($id = $r->validated('transfer_id')) {
                    $t = Transfer::whereKey($id)->where('asset_id', $a->id)->lockForUpdate()->firstOrFail();
                    Gate::authorize('submit', $t);
                    if (! in_array($t->status, ['draft', 'submitted'])) {
                        throw ValidationException::withMessages(['transfer_id' => 'Lampiran perpindahan final tidak dapat diubah.']);
                    }
                }
                if ($id = $r->validated('maintenance_id')) {
                    Maintenance::whereKey($id)->where('asset_id', $a->id)->firstOrFail();
                }
                $path = $file->store('attachments/'.$a->id, 'local');
                $attachment = Attachment::create(['asset_id' => $a->id, 'transfer_id' => $r->validated('transfer_id'), 'maintenance_id' => $r->validated('maintenance_id'),
                    'path' => $path, 'original_name' => mb_substr(basename(str_replace('\\', '/', $file->getClientOriginalName())), 0, 240), 'mime' => $file->getMimeType(), 'size' => $file->getSize(), 'uploaded_by' => $r->user()->id]);
                History::audit($r->user(), 'attachment.created', $attachment, ['asset_id' => $a->id, 'mime' => $attachment->mime, 'size' => $attachment->size]);
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            } throw $e;
        }

        return back()->with('success', 'Lampiran disimpan secara privat.');
    }

    public function download(Attachment $attachment)
    {
        Gate::authorize('view', $attachment);
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name, ['Content-Type' => $attachment->mime, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }
}
