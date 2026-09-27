<x-layout title="Experiencias en revisión" :noindex="true">
  <main class="container wizard" style="max-width:920px">
    <p class="eyebrow">Administración</p>
    <h1 class="title">Experiencias <span class="hl">en revisión</span>.</h1>
    @include('admin.partials.nav')

    <section class="wizard__panel">
      <h2>Para revisar</h2>
      <p>Revisá que el texto y la foto sean reales y respetuosos: nada de insultos, datos de contacto ni fotos de stock. Si la rechazás, el motivo le llega al anfitrión por mail.</p>
      @forelse ($pending as $experience)
        <article class="review-item">
          <img class="review-item__img" src="{{ $experience->coverUrl('card') }}" alt="" loading="lazy">
          <div>
            <h3 style="margin:0"><a href="{{ route('experiencias.show', $experience) }}" target="_blank" rel="noopener">{{ $experience->title }}</a></h3>
            <p class="hint" style="margin:4px 0 8px">
              {{ $experience->category->name }} · {{ $experience->placeLabel() }} · {{ money($experience->price) }} · enviada {{ $experience->created_at->diffForHumans() }}<br>
              <a href="{{ route('admin.usuarios.show', $experience->host->user) }}">{{ $experience->host->display_name }}</a>
              <x-verification-badge :level="$experience->host->user->verification_level" />
            </p>
            <p style="margin:0 0 6px"><strong>{{ $experience->summary }}</strong></p>
            <details><summary>Descripción</summary><p style="white-space:pre-line">{{ $experience->description }}</p></details>
            @if ($experience->dietary_options?->isNotEmpty())
              <p class="card__diet">{{ $experience->dietary_options->map->label()->join(' · ') }}</p>
            @endif
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:12px">
              <form method="post" action="{{ route('admin.experiencias.aprobar', $experience) }}">@csrf<button class="btn btn--secondary btn--sm">Aprobar</button></form>
              <form method="post" action="{{ route('admin.experiencias.rechazar', $experience) }}" style="display:flex;gap:6px;flex:1;min-width:240px">@csrf<input name="reason" class="inline-input" style="flex:1" maxlength="500" placeholder="Motivo para el anfitrión" aria-label="Motivo del rechazo" required><button class="btn btn--tertiary btn--sm">Rechazar</button></form>
            </div>
          </div>
        </article>
      @empty
        <p class="hint">No hay experiencias para revisar.</p>
      @endforelse
    </section>

    @if ($awaitingHost->isNotEmpty())
      <section class="wizard__panel">
        <h2>Aprobadas, esperando al anfitrión</h2>
        <p>El contenido está bien. Se publican solas cuando el anfitrión valide su domicilio (nivel 3).</p>
        <div class="summary">
          @foreach ($awaitingHost as $experience)
            <div><span><a href="{{ route('experiencias.show', $experience) }}">{{ $experience->title }}</a></span><strong><a href="{{ route('admin.usuarios.show', $experience->host->user) }}">{{ $experience->host->display_name }}</a></strong></div>
          @endforeach
        </div>
      </section>
    @endif
  </main>
</x-layout>
