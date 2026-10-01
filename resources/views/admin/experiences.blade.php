<x-layout title="Experiencias" :noindex="true">
  <main class="container wizard" style="max-width:920px">
    <p class="eyebrow">Administración</p>
    <h1 class="title"><span class="hl">Experiencias</span>.</h1>
    @include('admin.partials.nav')

    <section class="wizard__panel">
      <h2>Para revisar</h2>
      <p>Revisá que el texto y la foto sean reales y respetuosos: nada de insultos, datos de contacto ni fotos de stock. Si la rechazás, el motivo le llega al anfitrión por mail.</p>
      @forelse ($pending as $experience)
        <article class="review-item">
          @if ($coverUrl = $experience->coverUrl('card'))
            <img class="review-item__img" src="{{ $coverUrl }}" alt="Foto de {{ $experience->title }}" loading="lazy">
          @else
            <div class="review-item__img review-item__img--empty">
              @if ($experience->latestCoverUpload?->isProcessing())
                La foto se está procesando. Si en unos minutos sigue así, revisá que la cola esté corriendo.
              @elseif ($experience->latestCoverUpload?->status === 'failed')
                No se pudo procesar la foto. Pedile al anfitrión que suba otra.
              @else
                Sin foto.
              @endif
            </div>
          @endif
          <div>
            <h3 style="margin:0"><a href="{{ route('admin.experiencias.show', $experience) }}">{{ $experience->title }}</a></h3>
            <p class="hint" style="margin:4px 0 8px">
              {{ $experience->category->name }} · {{ $experience->placeLabel() }} · {{ money($experience->price) }} · enviada {{ $experience->updated_at->diffForHumans() }}<br>
              <x-admin-user-link :user="$experience->host->user" :label="$experience->host->display_name" />
              <x-verification-badge :level="$experience->host->user->verification_level" />
            </p>
@if ($ai = $experience->latestModeration)
              <p class="ai-review"><span class="badge {{ $ai->verdict->badgeClass() }}">{{ $ai->verdict->label() }}</span> @if ($ai->reason){{ $ai->reason }}@endif @if ($ai->categories)<small class="hint">({{ implode(', ', $ai->categories) }})</small>@endif</p>
            @endif
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
            <div><span><a href="{{ route('admin.experiencias.show', $experience) }}">{{ $experience->title }}</a></span><strong><x-admin-user-link :user="$experience->host->user" :label="$experience->host->display_name" /></strong></div>
          @endforeach
        </div>
      </section>
    @endif

    <section class="wizard__panel" id="todas">
      <div class="host-head">
        <h2 style="margin:0">Todas</h2>
        @if ($pendingAddresses)
          <form method="post" action="{{ route('admin.experiencias.direcciones') }}">@csrf<button class="btn btn--tertiary btn--sm">Normalizá {{ plural_es($pendingAddresses, 'dirección pendiente', 'direcciones pendientes') }} con Georef</button></form>
        @endif
      </div>
      <form method="get" action="{{ route('admin.experiencias') }}#todas" class="filters">
        <div class="field"><label for="q">Buscar</label><input id="q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Título o anfitrión"></div>
        <div class="field"><label for="estado">Estado</label>
          <select id="estado" name="estado">
            <option value="">Todos</option>
            @foreach ($statuses as $status)<option value="{{ $status->value }}" @selected(($filters['estado'] ?? null) === $status->value)>{{ (new \App\Models\Experience(['status' => $status]))->statusLabel() }}</option>@endforeach
          </select>
        </div>
        <span></span>
        <div class="filters__actions"><button class="btn btn--secondary btn--sm" type="submit">Filtrá</button><a class="btn btn--ghost btn--sm" href="{{ route('admin.experiencias') }}#todas">Limpiá</a></div>
      </form>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Experiencia</th><th>Estado</th><th>Opiniones</th><th>Reservas</th><th>Próxima fecha</th></tr></thead>
          <tbody>
            @forelse ($all as $experience)
              <tr>
                <td><a href="{{ route('admin.experiencias.show', $experience) }}"><strong>{{ $experience->title }}</strong></a><br><small class="hint">{{ $experience->host->display_name }} · {{ $experience->placeLabel() }}</small></td>
                <td><span class="badge {{ $experience->statusBadgeClass() }}">{{ $experience->statusLabel() }}</span></td>
                <td style="white-space:nowrap">@if ($experience->reviews_count)<x-stars :rating="$experience->rating_avg" /> {{ number_format($experience->rating_avg, 1, ',', '.') }} ({{ $experience->reviews_count }})@else<span class="hint">Sin opiniones</span>@endif</td>
                <td style="white-space:nowrap">{{ $experience->upcoming_bookings_count }} próximas<br><small class="hint">{{ $experience->bookings_count }} en total</small></td>
                <td style="white-space:nowrap">{{ $experience->upcomingDates->first()?->localStart()->translatedFormat('D j M · H:i') ?? '—' }}</td>
              </tr>
            @empty
              <tr><td colspan="5" class="hint">No hay experiencias con esos filtros.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div style="margin-top:16px">{{ $all->links() }}</div>
    </section>
  </main>
</x-layout>
