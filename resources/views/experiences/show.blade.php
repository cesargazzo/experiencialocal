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
  :title="__(':title en :city', ['title' => $experience->title, 'city' => $experience->city])"
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
            <p class="eyebrow">{{ __(':type con :name', ['type' => $experience->type_label, 'name' => $experience->host->publicName()]) }}</p>
            <h1>{{ $experience->title }}</h1>
            <div class="meta">
              <span><x-icon name="star" :size="16" /> {{ $experience->reviews_count > 0 ? number_format($experience->rating_avg, 1, ',', '.').' · '.plural_es($experience->reviews_count, __('opinión'), __('opiniones')) : __('Nueva en Tinku') }}</span>
              <span><x-icon name="clock" :size="16" /> {{ $experience->durationLabel() }}</span>
              <span><x-icon name="users" :size="16" /> {{ __('Hasta :count personas', ['count' => $experience->max_guests]) }}</span>
              <span><x-icon name="map-pin" :size="16" /> {{ $experience->placeLabel() }}</span>
              @if ($experience->status !== \App\Enums\ExperienceStatus::Published)<span class="meta--status">{{ $experience->statusLabel() }}</span>@endif
            </div>
            @if ($experience->isPublished())
              <div class="detail-hero__actions">
                <x-favorite-button :experience="$experience" :with-label="true" />
                <x-share-buttons :url="route('experiencias.show', $experience)" :title="$experience->title" />
              </div>
            @endif
          </div>
        </div>
      </div>
    </section>

    @auth
      @php($canEdit = auth()->user()->can('update', $experience))
      @php($canModerate = auth()->user()->hasTeamPermission(\App\Enums\TeamPermission::ModerateExperiences))
      @if ($canEdit || $canModerate)
        <div class="container">
          <div class="owner-bar">
            <span class="badge {{ $experience->statusBadgeClass() }}">{{ $experience->statusLabel() }}</span>
            <span class="owner-bar__text">
              @if ($canEdit)
                @switch ($experience->status)
                  @case (\App\Enums\ExperienceStatus::InReview)
                    {{ $experience->approved_at ? __('Ya la aprobamos: se publica cuando valides tu domicilio.') : __('La estamos revisando. Mientras tanto la podés editar.') }}
                    @break
                  @case (\App\Enums\ExperienceStatus::Draft)
                    {{ $experience->rejection_reason ? __('No la pudimos publicar. Motivo: :reason', ['reason' => $experience->rejection_reason]) : __('Es un borrador.') }}
                    @break
                  @case (\App\Enums\ExperienceStatus::Paused)
                    {{ __('La pausamos. Motivo: :reason', ['reason' => $experience->paused_reason]) }}
                    @break
                  @default
                    {{ __('Así la ve la gente.') }}
                @endswitch
              @else
                {{ __('Estás viendo esta experiencia como parte del equipo.') }}
              @endif
            </span>
            <span class="owner-bar__actions">
              @if ($canEdit)
                <a class="btn btn--secondary btn--sm" href="{{ route('anfitrion.experiencias.editar', $experience) }}">{{ __('Editala') }}</a>
                <a class="btn btn--ghost btn--sm" href="{{ route('anfitrion.experiencias.editar', $experience) }}#fechas"><x-icon name="calendar-blank" :size="16" /> {{ __('Fechas') }}</a>
              @endif
              @if ($canModerate)
                <a class="btn btn--tertiary btn--sm" href="{{ route('admin.experiencias.show', $experience) }}">{{ __('Ver en admin') }}</a>
              @endif
            </span>
          </div>
        </div>
      @endif
    @endauth

    <div class="container detail-layout">
      <div>
        <div class="detail-block"><h2>{{ __('La experiencia') }}</h2><p>{{ $experience->description }}</p></div>
        @if ($experience->galleryPhotos->isNotEmpty())
          @php($galleryItems = $experience->galleryPhotos->map(fn ($photo) => ['src' => $photo->url('large'), 'alt' => $photo->alt ?: $experience->title])->values())
          <div class="detail-block" id="fotos"><h2>{{ __('Fotos') }}</h2>
            <div class="gallery" x-data="{ open: null, items: @js($galleryItems), show(i) { this.open = (i + this.items.length) % this.items.length } }"
              x-on:keydown.escape.window="open = null" x-on:keydown.arrow-right.window="open !== null && show(open + 1)" x-on:keydown.arrow-left.window="open !== null && show(open - 1)">
              @foreach ($experience->galleryPhotos as $photo)
                <button type="button" class="gallery__item" x-on:click="show({{ $loop->index }})" aria-label="{{ __('Ver la foto :number de :total', ['number' => $loop->iteration, 'total' => $loop->count]) }}">
                  <img src="{{ $photo->url('thumb') }}" alt="{{ $photo->alt ?: $experience->title }}" loading="lazy" width="480" height="360">
                </button>
              @endforeach
              <div class="lightbox" x-show="open !== null" x-cloak x-transition.opacity role="dialog" aria-modal="true" aria-label="{{ __('Fotos de la experiencia') }}" x-on:click.self="open = null">
                <button type="button" class="lightbox__close" x-on:click="open = null" aria-label="{{ __('Cerrar') }}"><x-icon name="x" :size="24" /></button>
                <button type="button" class="lightbox__nav lightbox__nav--prev" x-on:click="show(open - 1)" aria-label="{{ __('Foto anterior') }}">‹</button>
                <img x-bind:src="open !== null ? items[open].src : ''" x-bind:alt="open !== null ? items[open].alt : ''">
                <button type="button" class="lightbox__nav lightbox__nav--next" x-on:click="show(open + 1)" aria-label="{{ __('Foto siguiente') }}">›</button>
                <span class="lightbox__count" x-text="open !== null ? (open + 1) + ' / ' + items.length : ''"></span>
              </div>
            </div>
          </div>
        @endif
        @if ($experience->difficulty || $experience->what_to_bring || $experience->min_age || $experience->features?->isNotEmpty())
          <div class="detail-block"><h2>{{ __('Bueno saber') }}</h2>
            <ul class="menu-list">
              @if ($experience->difficulty)<li><strong>{{ __('Dificultad :level', ['level' => Str::lower($experience->difficulty->label())]) }}</strong><span>{{ $experience->difficulty->hint() }}</span></li>@endif
              @if ($experience->what_to_bring)<li><strong>{{ $experience->difficulty ? __('Qué llevar y cómo vestirse') : __('Qué llevar') }}</strong><span>{{ $experience->what_to_bring }}</span></li>@endif
              @if ($experience->min_age)<li><strong>{{ __('Desde :age años', ['age' => $experience->min_age]) }}</strong><span>{{ __('Edad mínima para participar.') }}</span></li>@endif
              @foreach ($experience->features ?? [] as $feature)<li><strong>{{ $feature->label() }}</strong><span></span></li>@endforeach
            </ul>
          </div>
        @endif
        @if ($experience->dietary_options?->isNotEmpty())
          <div class="detail-block"><h2>{{ __('Opciones de comida') }}</h2>
            <ul class="menu-list">@foreach ($experience->dietary_options as $option)<li><strong>{{ $option->label() }}</strong><span>{{ $option->hint() }}</span></li>@endforeach</ul>
          </div>
        @endif
        @if ($experience->includes)
          <div class="detail-block"><h2>{{ __('Qué incluye') }}</h2><ul class="menu-list">@foreach ($experience->includes as $i)<li><strong>{{ $i['label'] }}</strong><span>{{ $i['text'] }}</span></li>@endforeach</ul></div>
        @endif
        @if ($zone = $experience->approximateLocation())
          <div class="detail-block"><h2>{{ __('Dónde') }}</h2>
            <p>{{ __(':place. En el mapa ves la zona aproximada: la dirección exacta te la pasamos cuando el anfitrión confirma tu reserva.', ['place' => $experience->placeLabel()]) }}</p>
            <div wire:ignore class="map" role="img" aria-label="{{ __('Mapa con la zona aproximada de la experiencia') }}"
              x-data="tinkuMap({ config: @js(config('tinku.maps')), lat: @js($zone['lat']), lng: @js($zone['lng']), radius: @js($zone['radius']) })"></div>
          </div>
        @endif
        <div class="detail-block" id="anfitrion"><h2>{{ __('Quién te recibe') }}</h2>
          <div class="host-card"><x-avatar :user="$experience->host->user" :size="56" /><div><strong>{{ $experience->host->publicName() }}</strong> · {{ __('En Tinku desde :year', ['year' => $experience->host->hosting_since?->year]) }}<br><x-verification-badge :level="$experience->host->user->verification_level" full /><p>{{ $experience->host->bio }}</p><x-social-links :links="$experience->host->user->socialLinksVisibleTo()" />
            @if (auth()->id() !== $experience->host->user_id && $experience->status === \App\Enums\ExperienceStatus::Published)
              @auth
                <form method="post" action="{{ route('mensajes.iniciar', $experience) }}">@csrf<button class="btn btn--tertiary btn--sm" type="submit"><x-icon name="envelope-simple" :size="16" /> {{ __('Preguntale a :name', ['name' => $experience->host->user->first_name]) }}</button></form>
              @else
                <a class="btn btn--tertiary btn--sm" href="{{ route('mensajes.escribir', $experience) }}"><x-icon name="envelope-simple" :size="16" /> {{ __('Ingresá para escribirle a :name', ['name' => $experience->host->user->first_name]) }}</a>
              @endauth
            @endif
          </div></div>
        </div>
        <div class="detail-block"><h2>{{ __('Opiniones') }}</h2>
          <div class="reviews">
            @forelse ($experience->reviews as $r)
              <div class="review"><header><strong>{{ $r->user->publicName() }}</strong><span>{{ Str::ucfirst($r->published_at->timezone($experience->timezone())->translatedFormat('F Y')) }} · <x-stars :rating="$r->rating" /><span class="sr-only">{{ __(':rating de 5', ['rating' => $r->rating]) }}</span></span></header><p>{{ $r->body }}</p>@if ($r->host_reply)<p class="review__reply"><strong>{{ __('Respuesta de :name:', ['name' => $experience->host->user->first_name]) }}</strong> {{ $r->host_reply }}</p>@endif</div>
            @empty
              <p>{{ __('Todavía no hay opiniones. Las escriben solo quienes fueron.') }}</p>
            @endforelse
          </div>
        </div>
        <div class="detail-block"><h2>{{ __('Condiciones') }}</h2>
          <ul>
            <li>{{ __('El pago se realiza dentro de Tinku y se cobra recién cuando el anfitrión confirma.') }}</li>
            <li>{{ __('Cancelación gratuita hasta 48 horas antes. Después, se retiene el 50%.') }}</li>
            <li>{{ __('La dirección exacta se comparte solo con reservas confirmadas.') }}</li>
            <li>{{ __('Avisá alergias o restricciones alimentarias al reservar.') }}</li>
          </ul>
        </div>
      </div>
      <aside>
        <livewire:book-experience :experience="$experience" />
      </aside>
    </div>
  </main>
</x-layout>
