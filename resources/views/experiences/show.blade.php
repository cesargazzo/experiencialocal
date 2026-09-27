@php
  $nextDate = $experience->upcomingDates->first(fn ($d) => $d->seatsLeft() > 0) ?? $experience->upcomingDates->first();
  $shareImage = $experience->coverUrl('og');
  $metaDescription = $experience->summary.' Con '.$experience->host->user->first_name.' en '.$experience->placeLabel().'. Desde '.money($experience->price).' por persona.';
  $jsonLd = ['@context' => 'https://schema.org', '@graph' => array_values(array_filter([
      $nextDate ? [
          '@type' => 'Event',
          'name' => $experience->title,
          'description' => $experience->description,
          'url' => route('experiencias.show', $experience),
          'image' => [$shareImage],
          'startDate' => $nextDate->localStart()->toIso8601String(),
          'endDate' => $nextDate->ends_at?->copy()->timezone($experience->timezone())->toIso8601String(),
          'eventStatus' => 'https://schema.org/EventScheduled',
          'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
          'location' => ['@type' => 'Place', 'name' => $experience->placeLabel(), 'address' => ['@type' => 'PostalAddress', 'addressLocality' => $experience->city, 'addressRegion' => $experience->province?->name, 'addressCountry' => $experience->country_code]],
          'organizer' => ['@type' => 'Person', 'name' => $experience->host->user->first_name],
          'maximumAttendeeCapacity' => $experience->max_guests,
          'offers' => ['@type' => 'Offer', 'url' => route('experiencias.show', $experience), 'price' => (string) $experience->price, 'priceCurrency' => $experience->currency, 'availability' => $nextDate->seatsLeft() > 0 ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut', 'validFrom' => $experience->published_at?->toIso8601String()],
      ] : null,
      ['@type' => 'BreadcrumbList', 'itemListElement' => [
          ['@type' => 'ListItem', 'position' => 1, 'name' => 'Tinku', 'item' => route('home')],
          ['@type' => 'ListItem', 'position' => 2, 'name' => $experience->category->name, 'item' => route('home', ['cat' => $experience->category->slug])],
          ['@type' => 'ListItem', 'position' => 3, 'name' => $experience->title],
      ]],
  ]))];
@endphp
<x-layout
  :title="$experience->title.' en '.$experience->city"
  :description="$metaDescription"
  :image="$shareImage"
  :image-alt="$experience->title"
  :canonical="route('experiencias.show', $experience)"
  :noindex="$experience->status !== \App\Enums\ExperienceStatus::Published"
  :json-ld="$jsonLd"
>
  <main>
    <section class="detail-hero">
      <div class="container">
        <div class="detail-hero__img" style="background-image:url('{{ $experience->coverUrl('hero') }}')">
          <div class="detail-hero__content">
            <p class="eyebrow">{{ $experience->type_label }} con {{ $experience->host->publicName() }}</p>
            <h1>{{ $experience->title }}</h1>
            <div class="meta">
              <span><x-icon name="star" :size="16" /> {{ $experience->reviews_count > 0 ? number_format($experience->rating_avg, 1, ',', '.').' · '.plural_es($experience->reviews_count, 'opinión', 'opiniones') : 'Nueva en Tinku' }}</span>
              <span><x-icon name="clock" :size="16" /> {{ $experience->durationLabel() }}</span>
              <span><x-icon name="users" :size="16" /> Hasta {{ $experience->max_guests }} personas</span>
              <span><x-icon name="map-pin" :size="16" /> {{ $experience->placeLabel() }}</span>
              @if ($experience->status !== \App\Enums\ExperienceStatus::Published)<span class="meta--status">{{ $experience->statusLabel() }}</span>@endif
            </div>
          </div>
        </div>
      </div>
    </section>

    @auth
      @php($canEdit = auth()->user()->can('update', $experience))
      @if ($canEdit || auth()->user()->isAdmin())
        <div class="container">
          <div class="owner-bar">
            <span class="badge {{ $experience->statusBadgeClass() }}">{{ $experience->statusLabel() }}</span>
            <span class="owner-bar__text">
              @if ($canEdit)
                @switch ($experience->status)
                  @case (\App\Enums\ExperienceStatus::InReview)
                    {{ $experience->approved_at ? 'Ya la aprobamos: se publica cuando valides tu domicilio.' : 'La estamos revisando. Mientras tanto la podés editar.' }}
                    @break
                  @case (\App\Enums\ExperienceStatus::Draft)
                    {{ $experience->rejection_reason ? 'No la pudimos publicar. Motivo: '.$experience->rejection_reason : 'Es un borrador.' }}
                    @break
                  @case (\App\Enums\ExperienceStatus::Paused)
                    La pausamos. Motivo: {{ $experience->paused_reason }}
                    @break
                  @default
                    Así la ve la gente.
                @endswitch
              @else
                Estás viendo esta experiencia como administrador.
              @endif
            </span>
            <span class="owner-bar__actions">
              @if ($canEdit)
                <a class="btn btn--secondary btn--sm" href="{{ route('anfitrion.experiencias.editar', $experience) }}">Editala</a>
                <a class="btn btn--ghost btn--sm" href="{{ route('anfitrion.experiencias.editar', $experience) }}#fechas"><x-icon name="calendar-blank" :size="16" /> Fechas</a>
              @endif
              @if (auth()->user()->isAdmin())
                <a class="btn btn--tertiary btn--sm" href="{{ route('admin.experiencias.show', $experience) }}">Ver en admin</a>
              @endif
            </span>
          </div>
        </div>
      @endif
    @endauth

    <div class="container detail-layout">
      <div>
        <div class="detail-block"><h2>La experiencia</h2><p>{{ $experience->description }}</p></div>
        @if ($experience->difficulty || $experience->what_to_bring || $experience->min_age || $experience->features?->isNotEmpty())
          <div class="detail-block"><h2>Bueno saber</h2>
            <ul class="menu-list">
              @if ($experience->difficulty)<li><strong>Dificultad {{ Str::lower($experience->difficulty->label()) }}</strong><span>{{ $experience->difficulty->hint() }}</span></li>@endif
              @if ($experience->what_to_bring)<li><strong>{{ $experience->difficulty ? 'Qué llevar y cómo vestirse' : 'Qué llevar' }}</strong><span>{{ $experience->what_to_bring }}</span></li>@endif
              @if ($experience->min_age)<li><strong>Desde {{ $experience->min_age }} años</strong><span>Edad mínima para participar.</span></li>@endif
              @foreach ($experience->features ?? [] as $feature)<li><strong>{{ $feature->label() }}</strong><span></span></li>@endforeach
            </ul>
          </div>
        @endif
        @if ($experience->dietary_options?->isNotEmpty())
          <div class="detail-block"><h2>Opciones de comida</h2>
            <ul class="menu-list">@foreach ($experience->dietary_options as $option)<li><strong>{{ $option->label() }}</strong><span>{{ $option->hint() }}</span></li>@endforeach</ul>
          </div>
        @endif
        @if ($experience->includes)
          <div class="detail-block"><h2>Qué incluye</h2><ul class="menu-list">@foreach ($experience->includes as $i)<li><strong>{{ $i['label'] }}</strong><span>{{ $i['text'] }}</span></li>@endforeach</ul></div>
        @endif
        @if ($zone = $experience->approximateLocation())
          <div class="detail-block"><h2>Dónde</h2>
            <p>{{ $experience->placeLabel() }}. En el mapa ves la zona aproximada: la dirección exacta te la pasamos cuando el anfitrión confirma tu reserva.</p>
            <div wire:ignore class="map" role="img" aria-label="Mapa con la zona aproximada de la experiencia"
              x-data="tinkuMap({ config: @js(config('tinku.maps')), lat: @js($zone['lat']), lng: @js($zone['lng']), radius: @js($zone['radius']) })"></div>
          </div>
        @endif
        <div class="detail-block"><h2>Quién te recibe</h2>
          <div class="host-card"><x-avatar :user="$experience->host->user" :size="56" /><div><strong>{{ $experience->host->publicName() }}</strong> · En Tinku desde {{ $experience->host->hosting_since?->year }}<br><x-verification-badge :level="$experience->host->user->verification_level" full /><p>{{ $experience->host->bio }}</p></div></div>
        </div>
        <div class="detail-block"><h2>Opiniones</h2>
          <div class="reviews">
            @forelse ($experience->reviews as $r)
              <div class="review"><header><strong>{{ $r->user->publicName() }}</strong><span>{{ Str::ucfirst($r->published_at->timezone($experience->timezone())->translatedFormat('F Y')) }} · <x-stars :rating="$r->rating" /><span class="sr-only">{{ $r->rating }} de 5</span></span></header><p>{{ $r->body }}</p></div>
            @empty
              <p>Todavía no hay opiniones. Las escriben solo quienes fueron.</p>
            @endforelse
          </div>
        </div>
        <div class="detail-block"><h2>Condiciones</h2>
          <ul>
            <li>El pago se realiza dentro de Tinku y se cobra recién cuando el anfitrión confirma.</li>
            <li>Cancelación gratuita hasta 48 horas antes. Después, se retiene el 50%.</li>
            <li>La dirección exacta se comparte solo con reservas confirmadas.</li>
            <li>Avisá alergias o restricciones alimentarias al reservar.</li>
          </ul>
        </div>
      </div>
      <aside>
        <livewire:book-experience :experience="$experience" />
      </aside>
    </div>
  </main>
</x-layout>
