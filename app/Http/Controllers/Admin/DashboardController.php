<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Infocentro;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the general administrator dashboard.
     */
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'infocentrosCount' => Infocentro::count(),
            'activeInfocentrosCount' => Infocentro::where('activo', true)->count(),
        ]);
    }
}
