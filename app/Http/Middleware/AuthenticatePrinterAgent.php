<?php

namespace App\Http\Middleware;

use App\Models\PrinterAgent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatePrinterAgent
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();

        if ($plainToken === null) {
            return response()->json(['message' => 'Token del agente requerido.'], 401);
        }

        $agent = PrinterAgent::query()
            ->where('token_hash', hash('sha256', $plainToken))
            ->where('active', true)
            ->first();

        if ($agent === null) {
            return response()->json(['message' => 'Token del agente inválido.'], 401);
        }

        $request->attributes->set('printerAgent', $agent);

        return $next($request);
    }
}
