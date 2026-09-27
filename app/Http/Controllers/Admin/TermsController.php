<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TermsVersion;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TermsController extends Controller
{
    public function index(): View
    {
        $required = TermsVersion::required();

        return view('admin.terms.index', [
            'versions' => TermsVersion::query()->withCount('acceptances')->latest('id')->get(),
            'current' => TermsVersion::current(),
            'required' => $required,
            'usersCount' => User::count(),
            'pendingCount' => $required
                ? User::whereDoesntHave('termsAcceptances.version', fn ($q) => $q->where('published_at', '>=', $required->published_at))->count()
                : 0,
        ]);
    }

    public function create(): View
    {
        $last = TermsVersion::query()->latest('id')->first();

        return view('admin.terms.form', ['terms' => new TermsVersion([
            'title' => $last?->title ?? 'Términos y condiciones de Tinku',
            'body' => $last?->body ?? '',
            'requires_reacceptance' => true,
        ])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $terms = TermsVersion::create([...$this->validated($request), 'created_by' => $request->user()->id]);

        return redirect()->route('admin.terminos.edit', $terms)->with('status', 'Guardamos el borrador '.$terms->version.'.');
    }

    public function edit(TermsVersion $terms): View
    {
        return view('admin.terms.form', ['terms' => $terms]);
    }

    public function update(Request $request, TermsVersion $terms): RedirectResponse
    {
        abort_if($terms->isPublished(), 409, 'Una versión publicada no se edita: creá una nueva.');

        $terms->update($this->validated($request, $terms));

        return back()->with('status', 'Guardamos el borrador '.$terms->version.'.');
    }

    public function publish(Request $request, TermsVersion $terms): RedirectResponse
    {
        abort_if($terms->isPublished(), 409, 'Esta versión ya está publicada.');

        $terms->publish($request->user());

        return redirect()->route('admin.terminos')->with('status', $terms->requires_reacceptance
            ? "Publicamos la versión {$terms->version}. Todas las cuentas la van a tener que aceptar en su próximo ingreso."
            : "Publicamos la versión {$terms->version}. No hace falta volver a aceptarla.");
    }

    public function acceptances(TermsVersion $terms): View
    {
        return view('admin.terms.acceptances', [
            'terms' => $terms,
            'acceptances' => $terms->acceptances()->with('user')->latest('accepted_at')->paginate(50),
        ]);
    }

    /**
     * @return array{version: string, title: string, body: string, changes_summary: ?string, requires_reacceptance: bool}
     */
    private function validated(Request $request, ?TermsVersion $terms = null): array
    {
        $data = $request->validate([
            'version' => ['required', 'string', 'max:20', Rule::unique('terms_versions', 'version')->ignore($terms)],
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'min:200'],
            'changes_summary' => ['nullable', 'string', 'max:2000'],
            'requires_reacceptance' => ['boolean'],
        ], ['version.unique' => 'Ya existe una versión con ese número.', 'body.min' => 'El texto parece incompleto.']);

        return [...$data, 'requires_reacceptance' => $request->boolean('requires_reacceptance')];
    }
}
