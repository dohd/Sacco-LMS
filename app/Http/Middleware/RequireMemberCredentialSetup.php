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
        $routeName = optional($request->route())->getName();
        if ($user->hasRoleCode('member') && $member && $member->kyc_status === 'complete'
            && in_array($routeName, ['memberships.edit', 'memberships.update'], true)) {
            return redirect()->route('memberships.show', $member->member_application_id)
                ->with('warning', 'Your KYC details have been verified and can no longer be edited. Contact an administrator if a correction is needed.');
        }

        if ($member && $member->kyc_status !== 'complete') {
            $allowedRoutes = ['member.kyc.show', 'member.kyc.store', 'member.kyc.pending', 'documents.show', 'logout'];
            if (in_array($routeName, $allowedRoutes, true)) return $next($request);
            if ($routeName === 'memberships.show' && $member->kyc_submitted_at) return $next($request);

            return $member->kyc_submitted_at
                ? redirect()->route('member.kyc.pending')
                : redirect()->route('member.kyc.show');
        }

        return $next($request);
    }
}
