<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AllowIframeFromWebsiteB
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);        

         // Ensure headers and cookies exist
        if (method_exists($response, 'header')) {
            // Remove default X-Frame-Options if it exists
            $response->headers->remove('X-Frame-Options');

            // Set CSP to authorize Website B
            $response->headers->set('Content-Security-Policy', "frame-ancestors 'self' " . config('session.iframe_website'));
        }

        // Force the Partitioned attribute onto Laravel's session cookies
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === 'laravel_session' || $cookie->getName() === 'XSRF-TOKEN') {
                // Re-queue the cookie with the modern Partitioned string appended
                header('Set-Cookie: ' . $cookie->__toString() . '; Partitioned', false);
            }
        }

        return $response;
    }
}
