<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminSections;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Entrada a la administración: lleva a la primera sección que la persona puede abrir. */
class AdminHomeController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $first = AdminSections::for($request->user())[0] ?? null;
        abort_if($first === null, 403);

        return redirect()->route($first['route']);
    }
}
