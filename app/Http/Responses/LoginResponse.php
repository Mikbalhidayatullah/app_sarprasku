<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as Contract;

class LoginResponse implements Contract
{
    public function toResponse($request)
    {
        $target = $request->session()->pull('url.intended', '/');
        $origin = rtrim($request->getSchemeAndHttpHost(), '/');
        if (str_starts_with($target, $origin.'/')) {
            $target = substr($target, strlen($origin));
        }
        if (! str_starts_with($target, '/') || str_starts_with($target, '//') || str_contains($target, '\\') || preg_match('/[\x00-\x20]/', $target)) {
            $target = '/';
        }

        return $request->wantsJson() ? response()->json(['two_factor' => false]) : redirect($target);
    }
}
