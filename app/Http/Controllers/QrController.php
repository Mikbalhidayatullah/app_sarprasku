<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Support\Facades\Gate;

class QrController extends Controller
{
    public function __invoke(string $token)
    {
        $a = Asset::where('qr_token', $token)->firstOrFail();
        Gate::authorize('view', $a);

        return redirect()->route('assets.show', $a);
    }
}
