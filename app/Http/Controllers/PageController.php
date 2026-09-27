<?php

namespace App\Http\Controllers;

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
}
