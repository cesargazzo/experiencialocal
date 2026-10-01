<x-layout :title="$experience->title" :noindex="true">
  <main class="container wizard" style="max-width:920px">
    <p class="eyebrow"><a href="{{ route('admin.experiencias') }}#todas">Experiencias</a></p>
    <h1 class="title" style="margin-bottom:8px">{{ $experience->title }}</h1>
    <p style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:0 0 24px">
      <span class="badge {{ $experience->statusBadgeClass() }}">{{ $experience->statusLabel() }}</span>
      <span class="hint">Con <x-admin-user-link :user="$experience->host->user" :label="$experience->host->display_name" /> · {{ $experience->placeLabel() }} · {{ money($experience->price) }} por persona</span>
      <a href="{{ route('experiencias.show', $experience) }}">Mirá la ficha</a>
    </p>

    @if ($experience->paused_reason)
      <p class="notice">Pausada. Motivo: {{ $experience->paused_reason }}</p>
    @elseif ($experience->rejection_reason)
      <p class="notice">Rechazada. Motivo: {{ $experience->rejection_reason }}</p>
    @endif

    <section class="wizard__panel">
      <h2>Estado</h2>
      <div class="summary">
        <div><span>Aprobada</span><strong>{{ $experience->approved_at ? $experience->approved_at->timezone(config('tinku.timezone'))->format('d/m/Y H:i').($experience->approver ? ' · '.$experience->approver->name : '') : '—' }}</strong></div>
        <div><span>Publicada</span><strong>{{ $experience->published_at?->timezone(config('tinku.timezone'))->format('d/m/Y H:i') ?? '—' }}</strong></div>
        <div><span>Opiniones</span><strong>{{ $experience->reviews_count ? number_format($experience->rating_avg, 1, ',', '.').' de 5 · '.plural_es($experience->reviews_count, 'opinión', 'opiniones') : 'Sin opiniones' }}</strong></div>
        <div><span>Próxima fecha</span><strong>{{ $experience->upcomingDates->first()?->localStart()->translatedFormat('D j M · H:i') ?? 'Sin fechas' }}</strong></div>
      </div>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:16px">
        @if ($experience->status === \App\Enums\ExperienceStatus::InReview && ! $experience->approved_at)
          <form method="post" action="{{ route('admin.experiencias.aprobar', $experience) }}">@csrf<button class="btn btn--secondary btn--sm">Aprobar</button></form>
          <form method="post" action="{{ route('admin.experiencias.rechazar', $experience) }}" style="display:flex;gap:6px;flex:1;min-width:240px">@csrf<input name="reason" class="inline-input" style="flex:1" maxlength="500" placeholder="Motivo para el anfitrión" aria-label="Motivo del rechazo" required><button class="btn btn--tertiary btn--sm">Rechazar</button></form>
        @elseif ($experience->status === \App\Enums\ExperienceStatus::Paused)
          <form method="post" action="{{ route('admin.experiencias.reactivar', $experience) }}">@csrf<button class="btn btn--secondary btn--sm">Reactivar</button></form>
        @else
          <form method="post" action="{{ route('admin.experiencias.pausar', $experience) }}" style="display:flex;gap:6px;flex:1;min-width:240px">@csrf<input name="reason" class="inline-input" style="flex:1" maxlength="500" placeholder="Motivo para el anfitrión" aria-label="Motivo de la pausa" required><button class="btn btn--tertiary btn--sm">Pausar</button></form>
        @endif
      </div>
      @error('reason')<p class="error" style="display:block">{{ $message }}</p>@enderror
      <p class="hint" style="margin-top:8px">Pausar la saca de la búsqueda y frena las reservas nuevas. Las que ya existen siguen. El anfitrión recibe el motivo por mail.</p>
    </section>

    <section class="wizard__panel">
      <h2>Punto de encuentro</h2>
      @error('address')<p class="notice" role="alert">{{ $message }}</p>@enderror
      <div class="summary">
        <div><span>Dirección</span><strong>{{ $experience->meeting_address ?? 'Sin cargar' }}</strong></div>
        <div><span>Normalizada con Georef</span><strong>{{ $experience->address_normalized_at?->timezone(config('tinku.timezone'))->format('d/m/Y H:i') ?? 'No' }}</strong></div>
        <div><span>Coordenadas</span><strong>{{ $experience->hasLocation() ? number_format($experience->latitude, 6, ',', '.').' · '.number_format($experience->longitude, 6, ',', '.') : 'Sin marcar' }}</strong></div>
      </div>
      @if ($experience->hasLocation())
        <div wire:ignore class="map" style="margin-top:16px" x-data="tinkuMap({ config: @js(config('tinku.maps')), lat: @js($experience->latitude), lng: @js($experience->longitude), zoom: 16 })"></div>
      @endif
      @if ($experience->meeting_address)
        <form method="post" action="{{ route('admin.experiencias.normalizar', $experience) }}" style="margin-top:12px">@csrf<button class="btn btn--tertiary btn--sm">Normalizá con Georef</button></form>
      @endif
    </section>

    <section class="wizard__panel">
      <h2>Reservas</h2>
      @if ($bookings->isEmpty())
        <p class="hint">Todavía no tiene reservas.</p>
      @else
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>Código</th><th>Persona</th><th>Fecha</th><th>Personas</th><th>Estado</th><th>Total</th></tr></thead>
            <tbody>
              @foreach ($bookings as $booking)
                <tr>
                  <td>{{ $booking->code }}</td>
                  <td><x-admin-user-link :user="$booking->user" /></td>
                  <td style="white-space:nowrap">{{ $booking->date->localStart()->translatedFormat('D j M Y · H:i') }}</td>
                  <td>{{ $booking->guests }}</td>
                  <td>{{ $booking->status->label() }}</td>
                  <td style="white-space:nowrap">{{ money($booking->total) }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </section>

    <section class="wizard__panel">
      <h2>Opiniones</h2>
      <div class="reviews">
        @forelse ($reviews as $review)
          <div class="review">
            <header><strong>{{ $review->user->name }}</strong><span>{{ $review->created_at->timezone(config('tinku.timezone'))->format('d/m/Y') }} · <x-stars :rating="$review->rating" /><span class="sr-only">{{ $review->rating }} de 5</span>@unless ($review->published_at) · <em>sin publicar</em>@endunless</span></header>
            <p>{{ $review->body }}</p>
            @if ($review->host_reply)<p class="hint">Respuesta del anfitrión: {{ $review->host_reply }}</p>@endif
          </div>
        @empty
          <p class="hint">Todavía no tiene opiniones.</p>
        @endforelse
      </div>
    </section>
  </main>
</x-layout>
