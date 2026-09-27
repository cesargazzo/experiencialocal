<?php

namespace App\Http\Controllers;

use App\Models\TermsAcceptance;
use App\Models\TermsVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TermsController extends Controller
{
    public function show(): View
    {
        return view('terms.show', ['terms' => TermsVersion::current(), 'history' => TermsVersion::published()->latest('published_at')->get()]);
    }

    public function version(TermsVersion $terms): View
    {
        abort_unless($terms->isPublished() && $terms->published_at->isPast(), 404);

        return view('terms.show', ['terms' => $terms, 'history' => TermsVersion::published()->latest('published_at')->get()]);
    }

    public function acceptForm(Request $request): View|RedirectResponse
    {
        $current = TermsVersion::current();
        if (! $current || $request->user()->hasAcceptedRequiredTerms()) {
            return redirect()->intended(route('home'));
        }

        return view('terms.accept', [
            'terms' => $current,
            'hadAcceptedBefore' => $request->user()->termsAcceptances()->exists(),
        ]);
    }

    public function accept(Request $request): RedirectResponse
    {
        $current = TermsVersion::current();
        abort_unless($current, 404);

        $request->validate(
            ['terms_version_id' => ['required', 'integer', 'in:'.$current->id], 'accept' => ['accepted']],
            ['accept.accepted' => 'Para seguir tenés que aceptar los términos.', 'terms_version_id.in' => 'Los términos cambiaron mientras los leías. Revisá la versión nueva.'],
        );

        TermsAcceptance::record($request->user(), $current, $request, 'reacceptance');

        return redirect()->intended(route('home'))->with('status', 'Gracias. Aceptaste los términos '.$current->version.'.');
    }
}
