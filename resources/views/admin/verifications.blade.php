<x-layout title="Verificaciones pendientes">
  <main class="container wizard" style="max-width:920px">
    <p class="eyebrow">Administración</p>
    <h1 class="title">Verificaciones <span class="hl">pendientes</span>.</h1>
    <section class="wizard__panel">
      @forelse ($pending as $v)
        <div class="summary"><div style="align-items:center;gap:20px">
          <span><strong style="color:var(--ink)">{{ $v->user->name }}</strong> · {{ $v->user->email }}<br><small>{{ $v->type->value }} · {{ $v->provider->value }}{{ $v->document_country ? ' · '.$v->document_country : '' }} · enviada {{ $v->submitted_at?->diffForHumans() }}</small></span>
          <span style="display:flex;gap:8px;align-items:center">
            <form method="post" action="{{ route('admin.verificaciones.aprobar', $v) }}">@csrf<button class="btn btn--primary btn--sm">Aprobar</button></form>
            <form method="post" action="{{ route('admin.verificaciones.rechazar', $v) }}" style="display:flex;gap:6px">@csrf<input name="reason" placeholder="Motivo" required style="border:1.5px solid var(--line);border-radius:999px;padding:8px 12px;font-size:13px"><button class="btn btn--outline btn--sm">Rechazar</button></form>
          </span>
        </div></div>
      @empty
        <p style="color:var(--muted)">No hay verificaciones pendientes.</p>
      @endforelse
    </section>
  </main>
</x-layout>
