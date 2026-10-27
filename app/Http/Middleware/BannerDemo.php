<?php

namespace App\Http\Middleware;

use App\Support\RelojDemo;
use Closure;
use Illuminate\Http\Request;

/** Muestra una franja fija «Modo demostración» en todas las pantallas mientras el reloj simulado está activo. */
class BannerDemo
{
    public function handle(Request $request, Closure $next)
    {
        $resp = $next($request);

        if (!RelojDemo::activo()) return $resp;

        // Cookies (p. ej. XSRF-TOKEN) con vencimiento calculado con la hora simulada: si ya quedaron en el pasado
        // para el navegador, se corrigen con la hora real para que no se descarten.
        foreach ($resp->headers->getCookies() as $c) {
            if ($c->getExpiresTime() !== 0 && $c->getExpiresTime() < time()) {
                $resp->headers->setCookie($c->withExpires(time() + 7200));
            }
        }

        if (!method_exists($resp, 'getContent')) return $resp;
        if (!str_contains((string) $resp->headers->get('Content-Type'), 'text/html')) return $resp;

        $html = (string) $resp->getContent();
        if (!str_contains($html, '</body>')) return $resp;

        $hora = e(now()->format('d/m/Y H:i'));
        $banner = '<div style="position:fixed;left:12px;bottom:12px;z-index:99999;background:#b45309;color:#fff;'
            . 'font:600 12px/1.3 Arial,sans-serif;padding:8px 12px;border-radius:999px;box-shadow:0 4px 14px rgba(0,0,0,.25)">'
            . 'MODO DEMOSTRACIÓN · Fecha y hora simuladas: ' . $hora . '</div>';

        $resp->setContent(preg_replace('#</body>#i', $banner . '</body>', $html, 1));

        return $resp;
    }
}
