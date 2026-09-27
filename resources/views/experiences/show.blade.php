<x-layout :title="$experience->title" :description="$experience->summary">
  <main>
    <section class="detail-hero">
      <div class="container">
        <div class="detail-hero__img" style="background-image:url('{{ $experience->cover_image_url }}')">
          <div class="detail-hero__content">
            <p class="eyebrow">{{ $experience->type_label }} · {{ $experience->city }}</p>
            <h1>{{ $experience->title }}</h1>
            <div class="meta">
              <span>★ {{ number_format($experience->rating_avg, 1, ',', '.') }} · {{ plural_es($experience->reviews_count, 'opinión', 'opiniones') }}</span>
              <span>⏱ {{ $experience->durationLabel() }}</span>
              <span>👥 Hasta {{ $experience->max_guests }} personas</span>
              <span>📍 {{ $experience->city }}</span>
              @if ($experience->status !== \App\Enums\ExperienceStatus::Published)<span style="background:var(--amber);color:var(--ink)">{{ $experience->status->value === 'in_review' ? 'En revisión' : ucfirst($experience->status->value) }}</span>@endif
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
        <div class="detail-block"><h2>Tu anfitrión</h2>
          <div class="host-card"><span class="avatar">{{ $experience->host->user->initials() }}</span><div><strong>{{ $experience->host->display_name }}</strong> · Anfitrión desde {{ $experience->host->hosting_since?->year }}<br><x-verification-badge :level="$experience->host->user->verification_level" full /><p>{{ $experience->host->bio }}</p></div></div>
        </div>
        <div class="detail-block"><h2>Opiniones</h2>
          <div class="reviews">
            @forelse ($experience->reviews as $r)
              <div class="review"><header><strong>{{ $r->user->name }}</strong><span>{{ $r->published_at->translatedFormat('F Y') }} · <x-stars :rating="$r->rating" /></span></header><p>{{ $r->body }}</p></div>
            @empty
              <p style="color:var(--muted)">Todavía no hay opiniones. Las escriben solo quienes asistieron.</p>
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
