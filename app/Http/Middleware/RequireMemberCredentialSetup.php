<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RequireMemberCredentialSetup
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user();
        if (!$user) return $next($request);

        if ($user->member_login_setup_required) return redirect()->route('member.credentials.show');

        $member = $user->member;
        if ($member && $member->kyc_status !== 'complete') {
            $allowedRoutes = ['member.kyc.show', 'member.kyc.store', 'member.kyc.pending', 'logout'];
            $routeName = optional($request->route())->getName();
            if (in_array($routeName, $allowedRoutes, true)) return $next($request);

            return $member->kyc_submitted_at
                ? redirect()->route('member.kyc.pending')
                : redirect()->route('member.kyc.show');
        }

        return $next($request);
    }
}
