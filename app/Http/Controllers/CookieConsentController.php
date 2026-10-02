<?php

namespace App\Http\Controllers;

use App\Support\CookieConsent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class CookieConsentController extends Controller
{
    /** Guarda la elección. Si se rechazan las de análisis, borra las que Google Analytics ya hubiera dejado. */
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate(['analytics' => ['required', 'boolean']]);
        $consent = new CookieConsent((bool) $data['analytics']);

        Cookie::queue($consent->toCookie());
        if (! $consent->analytics) {
            foreach (array_keys($request->cookies->all()) as $name) {
                if ($name === '_ga' || str_starts_with($name, '_ga_')) {
                    Cookie::queue(Cookie::forget($name, '/', $request->getHost()));
                }
            }
        }

        return back()->with('status', $consent->analytics
            ? __('Guardamos tu elección: aceptaste las cookies de análisis.')
            : __('Guardamos tu elección: solo usamos las cookies necesarias.'));
    }
}
