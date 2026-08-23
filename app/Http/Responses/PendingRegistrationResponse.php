<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\RegisterResponse;
use Symfony\Component\HttpFoundation\Response;

class PendingRegistrationResponse implements RegisterResponse
{
    /**
     * Create the response for a newly registered, pending account.
     */
    public function toResponse($request): Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->wantsJson()) {
            return new JsonResponse([
                'message' => 'Dein Account wurde angelegt und wartet auf die Freischaltung.',
            ], 201);
        }

        return redirect()->route('login')->with(
            'status',
            'Dein Account wurde angelegt und wartet jetzt auf die Freischaltung durch eine:n Administrator:in.',
        );
    }
}
