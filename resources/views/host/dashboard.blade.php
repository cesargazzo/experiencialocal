<x-layout title="Tu espacio de anfitrión" :noindex="true">
  <main class="container wizard" style="max-width:920px">
    <p class="eyebrow">Anfitrión</p>
    <h1 class="title">Hola, <span class="hl">{{ Str::before($profile->display_name.' ', ' ') }}</span>.</h1>

    @unless ($profile->isActive())
      <p class="notice">
        Tu perfil de anfitrión está <strong>en revisión</strong>.
        @unless ($residenceValidated)
          Tus experiencias aprobadas se publican cuando valides tu domicilio en <a href="{{ route('verificacion') }}">tu centro de verificación</a>.
        @endunless
      </p>
    @endunless

    <section class="wizard__panel">
      <div class="host-head">
        <div>
          <h2 style="margin:0">Tus experiencias</h2>
          <p class="hint" style="margin:4px 0 0">
            Plan {{ $profile->plan->name }} · {{ $profile->plan->commissionPercent() }}% por reserva
            @if ($profile->plan->max_experiences) · hasta {{ plural_es($profile->plan->max_experiences, 'experiencia activa', 'experiencias activas') }}@endif
          </p>
        </div>
        @if ($canCreateAnother)
          <a class="btn btn--primary btn--sm" href="{{ route('anfitrion.registro') }}">Creá otra experiencia</a>
        @endif
      </div>
      @unless ($canCreateAnother)
        <p class="hint">Tu plan no permite más experiencias activas. Para sumar otra, <a href="{{ route('home') }}#planes">mirá los planes</a>.</p>
      @endunless

      @forelse ($experiences as $experience)
        @php
          $badge = match ($experience->status) {
              \App\Enums\ExperienceStatus::Published => 'badge--ok',
              \App\Enums\ExperienceStatus::InReview => 'badge--espera',
              \App\Enums\ExperienceStatus::Draft => $experience->rejection_reason ? 'badge--error' : 'badge--nivel-1',
              default => 'badge--nivel-1',
          };
          $next = $experience->upcomingDates->first();
        @endphp
        <article class="booking-item">
          <a class="booking-item__img" href="{{ route('experiencias.show', $experience) }}" style="background-image:url('{{ $experience->coverUrl('card') }}')" aria-hidden="true" tabindex="-1"></a>
          <div>
            <h3 class="booking-item__title"><a href="{{ route('experiencias.show', $experience) }}">{{ $experience->title }}</a></h3>
            <p class="booking-item__meta">
              <span class="badge {{ $badge }}">{{ $experience->statusLabel() }}</span>
              <span>{{ $experience->placeLabel() }} · {{ money($experience->price) }} por persona</span>
            </p>
            @switch (true)
              @case ($experience->status === \App\Enums\ExperienceStatus::InReview && ! $experience->approved_at)
                <p class="hint" style="margin:0 0 8px">La estamos revisando. Te avisamos por mail cuando esté aprobada.</p>
                @break
              @case ($experience->status === \App\Enums\ExperienceStatus::InReview)
                <p class="hint" style="margin:0 0 8px">Ya la aprobamos. Se publica cuando valides tu domicilio.</p>
                @break
              @case ($experience->status === \App\Enums\ExperienceStatus::Draft && $experience->rejection_reason)
                <p class="notice" style="margin:0 0 8px">No la pudimos publicar. Motivo: {{ $experience->rejection_reason }}</p>
                @break
            @endswitch
            @if ($experience->latestCoverUpload?->isProcessing())
              <p class="hint" style="margin:0 0 8px">La foto se está procesando.</p>
            @endif
            <ul class="booking-item__facts">
              <li><x-icon name="calendar-blank" :size="16" /> {{ $next ? Str::ucfirst($next->localStart()->translatedFormat('D j M · H:i')) : 'Sin fechas próximas' }}</li>
              <li><x-icon name="users" :size="16" /> {{ $experience->pending_bookings_count ? plural_es($experience->pending_bookings_count, 'reserva por confirmar', 'reservas por confirmar') : 'Sin reservas por confirmar' }}</li>
            </ul>
          </div>
        </article>
      @empty
        <p class="hint">Todavía no cargaste experiencias. <a href="{{ route('anfitrion.registro') }}">Creá la primera</a>.</p>
      @endforelse
    </section>
  </main>
</x-layout>
