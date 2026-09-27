@php
  $nextDate = $experience->upcomingDates->first(fn ($d) => $d->seatsLeft() > 0) ?? $experience->upcomingDates->first();
  $shareImage = $experience->coverUrl('og');
  $metaDescription = $experience->summary.' Con '.$experience->host->display_name.' en '.$experience->placeLabel().'. Desde '.money($experience->price).' por persona.';
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
          'organizer' => ['@type' => 'Person', 'name' => $experience->host->display_name],
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
            <p class="eyebrow">{{ $experience->type_label }} con {{ $experience->host->display_name }}</p>
            <h1>{{ $experience->title }}</h1>
            <div class="meta">
              <span><x-icon name="star" :size="16" /> {{ $experience->reviews_count > 0 ? number_format($experience->rating_avg, 1, ',', '.').' · '.plural_es($experience->reviews_count, 'opinión', 'opiniones') : 'Nueva en Tinku' }}</span>
              <span><x-icon name="clock" :size="16" /> {{ $experience->durationLabel() }}</span>
              <span><x-icon name="users" :size="16" /> Hasta {{ $experience->max_guests }} personas</span>
              <span><x-icon name="map-pin" :size="16" /> {{ $experience->placeLabel() }}</span>
              @if ($experience->status !== \App\Enums\ExperienceStatus::Published)<span class="meta--status">{{ $experience->status->value === 'in_review' ? 'En revisión' : ucfirst($experience->status->value) }}</span>@endif
            </div>
          </div>
        </div>
      </div>
    </section>

    <div class="container detail-layout">
      <div>
        <div class="detail-block"><h2>La experiencia</h2><p>{{ $experience->description }}</p></div>
        @if ($experience->includes)
          <div class="detail-block"><h2>Qué incluye</h2><ul class="menu-list">@foreach ($experience->includes as $i)<li><strong>{{ $i['label'] }}</strong><span>{{ $i['text'] }}</span></li>@endforeach</ul></div>
        @endif
        <div class="detail-block"><h2>Quién te recibe</h2>
          <div class="host-card"><x-avatar :user="$experience->host->user" :size="56" /><div><strong>{{ $experience->host->display_name }}</strong> · En Tinku desde {{ $experience->host->hosting_since?->year }}<br><x-verification-badge :level="$experience->host->user->verification_level" full /><p>{{ $experience->host->bio }}</p></div></div>
        </div>
        <div class="detail-block"><h2>Opiniones</h2>
          <div class="reviews">
            @forelse ($experience->reviews as $r)
              <div class="review"><header><strong>{{ $r->user->name }}</strong><span>{{ Str::ucfirst($r->published_at->timezone($experience->timezone())->translatedFormat('F Y')) }} · <x-stars :rating="$r->rating" /><span class="sr-only">{{ $r->rating }} de 5</span></span></header><p>{{ $r->body }}</p></div>
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
