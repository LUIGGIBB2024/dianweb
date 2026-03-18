<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AllowIframeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Permitir que otros sitios nos embeban (si fuera necesario)
        $response->headers->set('X-Frame-Options', 'ALLOW-FROM https://catalogo-vpfe.dian.gov.co/');

        // Política de Seguridad de Contenido (CSP) - ESTA ES LA CLAVE
        // Permite que nuestro sitio cargue frames de la DIAN y Wikipedia
        $response->headers->set('Content-Security-Policy', "frame-src 'self' https://catalogo-vpfe.dian.gov.co https://*.wikipedia.org;");

        return $response;
    }
}
