<x-layout title="Tu espacio de anfitrión" :noindex="true">
  <main class="container wizard" style="max-width:920px">
    <p class="eyebrow">Anfitrión</p>
    <h1 class="title">Hola, <span class="hl">{{ Str::before($profile->display_name.' ', ' ') }}</span>.</h1>
    @include('host.partials.nav')

    @unless ($profile->isActive())
      <p class="notice">
        Tu perfil de anfitrión está <strong>en revisión</strong>.
        @unless ($residenceValidated)
          Tus experiencias aprobadas se publican cuando valides tu domicilio en <a href="{{ route('verificacion') }}">tu centro de verificación</a>.
        @endunless
      </p>
    @endunless

    @error('booking')<p class="notice" role="alert">{{ $message }}</p>@enderror

    <section class="wizard__panel" id="reservas">
      <h2>Reservas por confirmar</h2>
      @forelse ($pendingBookings as $booking)
        @include('host.partials.booking', ['booking' => $booking, 'pending' => true])
      @empty
        <p class="hint">No tenés reservas esperando. Cuando alguien pida lugar, te avisamos acá y por mail.</p>
      @endforelse

      @if ($confirmedBookings->isNotEmpty())
        <h2 style="margin-top:32px">Próximas confirmadas</h2>
        @foreach ($confirmedBookings as $booking)
          @include('host.partials.booking', ['booking' => $booking, 'pending' => false])
        @endforeach
      @endif
    </section>

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
        @php($next = $experience->upcomingDates->first())
        <article class="booking-item">
          <a class="booking-item__img" href="{{ route('experiencias.show', $experience) }}" style="background-image:url('{{ $experience->coverUrl('card') }}')" aria-hidden="true" tabindex="-1"></a>
          <div>
            <h3 class="booking-item__title"><a href="{{ route('experiencias.show', $experience) }}">{{ $experience->title }}</a></h3>
            <p class="booking-item__meta">
              <span class="badge {{ $experience->statusBadgeClass() }}">{{ $experience->statusLabel() }}</span>
              <span>{{ $experience->placeLabel() }} · {{ money($experience->price) }} por persona</span>
            </p>
            @switch (true)
              @case ($experience->status === \App\Enums\ExperienceStatus::InReview && ! $experience->approved_at)
                <p class="hint" style="margin:0 0 8px">La estamos revisando. Te avisamos por mail cuando esté aprobada.</p>
                @break
              @case ($experience->status === \App\Enums\ExperienceStatus::InReview)
                <p class="hint" style="margin:0 0 8px">Ya la aprobamos. Se publica cuando valides tu domicilio.</p>
                @break
              @case ($experience->status === \App\Enums\ExperienceStatus::Paused)
                <p class="notice" style="margin:0 0 8px">La pausamos. Motivo: {{ $experience->paused_reason }}. Respondé el mail que te mandamos para conversarlo.</p>
                @break
              @case ($experience->status === \App\Enums\ExperienceStatus::Draft && $experience->rejection_reason)
                <p class="notice" style="margin:0 0 8px">No la pudimos publicar. Motivo: {{ $experience->rejection_reason }}</p>
                @break
            @endswitch
            @if ($experience->latestCoverUpload?->isProcessing())
              <p class="hint" style="margin:0 0 8px">La foto se está procesando.</p>
            @endif
            <p style="margin:0 0 8px;display:flex;gap:12px;flex-wrap:wrap">
              <a class="btn btn--tertiary btn--sm" href="{{ route('anfitrion.experiencias.editar', $experience) }}">Editala</a>
              <a class="btn btn--ghost btn--sm" href="{{ route('anfitrion.experiencias.editar', $experience) }}#fechas"><x-icon name="calendar-blank" :size="16" /> Fechas</a>
            </p>
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

    <section class="wizard__panel" id="opiniones">
      <h2>Opiniones</h2>
      @error('reply')<p class="notice" role="alert">{{ $message }}</p>@enderror
      @forelse ($reviews as $review)
        <div class="review" style="padding:16px 0;border-top:1px solid var(--linea)">
          <header><strong>{{ $review->user->publicName() }}</strong><span>{{ $review->experience->title }} · <x-stars :rating="$review->rating" /></span></header>
          <p>{{ $review->body }}</p>
          @if ($review->host_reply)
            <p class="review__reply"><strong>Tu respuesta:</strong> {{ $review->host_reply }}</p>
          @else
            <form method="post" action="{{ route('anfitrion.opiniones.responder', $review) }}" style="display:grid;gap:8px">
              @csrf
              <textarea name="host_reply" rows="2" maxlength="1000" required placeholder="Agradecé o contá algo más. Tu respuesta se ve en la experiencia."></textarea>
              <span><button class="btn btn--tertiary btn--sm" type="submit">Respondé</button></span>
            </form>
          @endif
        </div>
      @empty
        <p class="hint">Todavía no tenés opiniones. Llegan después de cada experiencia realizada.</p>
      @endforelse
    </section>
  </main>
</x-layout>
