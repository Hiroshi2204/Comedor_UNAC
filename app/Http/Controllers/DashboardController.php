<?php

namespace App\Http\Controllers;

use App\Services\DashboardAdminService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $r, DashboardAdminService $servicio)
    {
        return view('admin.dashboard', [
            'usuario' => $r->user('web'),
            'd'       => $servicio->datos(now()),
        ]);
    }
}