<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Experience;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function robots(): Response
    {
        $lines = config('tinku.indexable')
            ? [
                'User-agent: *',
                'Allow: /',
                'Disallow: /admin',
                'Disallow: /anfitrion',
                'Disallow: /cuenta',
                'Disallow: /verificacion',
                'Disallow: /ingresar',
                'Disallow: /registrarme',
                'Disallow: /livewire',
                '',
                'Sitemap: '.route('sitemap'),
            ]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n")->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function sitemap(): Response
    {
        // Sin indexación no se ofrece mapa del sitio.
        abort_unless(config('tinku.indexable'), 404);

        $experiences = Experience::published()->select(['slug', 'updated_at'])->orderByDesc('updated_at')->get();

        return response()
            ->view('seo.sitemap', [
                'experiences' => $experiences,
                'categories' => Category::orderBy('sort_order')->pluck('slug'),
                'lastModified' => $experiences->max('updated_at') ?? now(),
            ])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
