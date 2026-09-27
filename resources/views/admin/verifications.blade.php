<x-layout title="Verificaciones pendientes" :noindex="true">
  <main class="container wizard" style="max-width:920px">
    <p class="eyebrow">Administración</p>
    <h1 class="title">Verificaciones <span class="hl">pendientes</span>.</h1>
    @include('admin.partials.nav')
    <section class="wizard__panel">
      @error('verification')<p class="notice" role="alert">{{ $message }}</p>@enderror
      @forelse ($pending as $v)
        <div class="summary"><div style="align-items:center;gap:20px">
          <span><strong>{{ $v->user->name }}</strong> · {{ $v->user->email }}<br><small>{{ $v->type->value }} · {{ $v->provider->value }}{{ $v->document_country ? ' · '.$v->document_country : '' }} · enviada {{ $v->submitted_at?->diffForHumans() }}</small></span>
          <span style="display:flex;gap:8px;align-items:center">
            <form method="post" action="{{ route('admin.verificaciones.aprobar', $v) }}">@csrf<button class="btn btn--secondary btn--sm">Aprobar</button></form>
            <form method="post" action="{{ route('admin.verificaciones.rechazar', $v) }}" style="display:flex;gap:6px">@csrf<input name="reason" class="inline-input" placeholder="Motivo" aria-label="Motivo del rechazo" required><button class="btn btn--tertiary btn--sm">Rechazar</button></form>
          </span>
        </div></div>
      @empty
        <p class="hint">No hay verificaciones pendientes.</p>
      @endforelse
    </section>
  </main>
</x-layout>
