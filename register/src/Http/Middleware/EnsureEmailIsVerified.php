<?php

namespace Boy132\Register\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified as BaseEnsureEmailIsVerified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;

class EnsureEmailIsVerified extends BaseEnsureEmailIsVerified
{
    public function handle($request, Closure $next, $redirectToRoute = null): Response|RedirectResponse|null
    {
        if (!$request->user() || ($request->user() instanceof User && is_null($request->user()->email_verified_at))) {
            return $request->expectsJson()
                ? abort(403, 'Your email address is not verified.')
                : Redirect::guest(URL::route($redirectToRoute ?: 'verification.notice'));
        }

        return $next($request);
    }
}
