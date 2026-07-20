<?php

namespace App\Http\Middleware;

use App\Http\Services\ZohoService;
use App\Models\GlobalConfig;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class LoadConfig
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
        // Set dynamic config values
        $configs = GlobalConfig::pluck('value', 'key');
        foreach ($configs as $key => $value) {
            Config::set($key, $value);
        }

        // check validity of access token and auto-refresh
        $tokenExpiry = config('ZOHO_ACCESS_TOKEN_EXPIRY')? strtotime(config('ZOHO_ACCESS_TOKEN_EXPIRY')) : 0;
        if (time() >= $tokenExpiry) {
            // $service = new \App\Http\Services\ZohoService;
            $service = app(ZohoService::class); 
            $resp = $service->refreshToken();
            if (isset($resp->access_token)) {
                $updates = [
                    'ZOHO_ACCESS_TOKEN_EXPIRES_IN' => $resp->expires_in,
                    'ZOHO_ACCESS_TOKEN_EXPIRY' => date('Y-m-d H:i:s', time() + $resp->expires_in),
                    'ZOHO_ACCESS_TOKEN' => $resp->access_token,
                ];
                foreach ($updates as $key => $value) {
                    GlobalConfig::where('key', $key)->update(['value' => $value]); 
                }               
            }
        }

        return $next($request);
    }
}
