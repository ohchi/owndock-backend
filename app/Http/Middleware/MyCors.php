<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class MyCors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $h = $response->headers;
        $h->set('Access-Control-Allow-Origin', config('app.frontend.url'));
        $h->set('Access-Control-Allow-Credentials', 'true');
        $h->set('Access-Control-Allow-Methods', 'GET,PUT,POST,DELETE,OPTIONS');
        $h->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, Content-Length, X-Requested-With');

        return $response;
    }
}
