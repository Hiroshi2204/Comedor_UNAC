<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Becado;
use App\Models\Sancion;
use App\Models\UsuarioSistema;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        /** @var UsuarioSistema $usuario */
        $usuario = Auth::guard('web')->user();

        if (!$usuario) {
            abort(401);
        }

        if ($usuario->esAdministrador()) {
            $stats = [
                'tickets_hoy' => Ticket::whereDate('fecha', today())->count(),

                'tickets_recogidos_hoy' => Ticket::whereDate('fecha', today())
                    ->where('estado', 'recogido')
                    ->count(),

                'becados_activos' => Becado::activos()->count(),

                'sanciones_activas' => Sancion::where('estado', 'activa')->count(),
            ];

            return view('dashboard.admin', compact('usuario', 'stats'));
        }

        return view('dashboard.trabajador', compact('usuario'));
    }
}
