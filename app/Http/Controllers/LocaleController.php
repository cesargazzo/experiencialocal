<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Cambia el idioma de la interfaz y lo recuerda en la cuenta. */
class LocaleController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate(['locale' => ['required', Rule::in(array_keys(config('tinku.locales')))]]);

        $request->session()->put('locale', $data['locale']);
        $request->user()?->forceFill(['locale' => $data['locale']])->save();

        return back();
    }
}
