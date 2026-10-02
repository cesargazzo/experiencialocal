<?php

namespace App\Http\Controllers;

use App\Support\CookieConsent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function help(): View
    {
        return view('pages.help');
    }

    public function safety(): View
    {
        return view('pages.safety');
    }

    public function cookies(Request $request): View
    {
        return view('pages.cookies', [
            'consent' => CookieConsent::fromRequest($request),
            'analyticsEnabled' => CookieConsent::hasOptionalCookies(),
        ]);
    }
}
