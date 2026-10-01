@php($guest = $booking->user)
<article class="host-booking">
  <x-avatar :user="$guest" :size="56" />
  <div>
    <p class="host-booking__who">
      <strong>{{ $guest->name }}</strong>
      <x-verification-badge :level="$guest->verification_level" full />
      @if (($noShowCount ?? 0) > 0)
        <span class="badge badge--espera">{{ $noShowCount === 1 ? 'No se presentó 1 vez' : "No se presentó {$noShowCount} veces" }} en el último año</span>
      @endif
    </p>
    <p class="hint" style="margin:2px 0 0">
      {{ $guest->locationLabel() ?? 'Sin ciudad cargada' }}
      · en Tinku desde {{ $guest->created_at->translatedFormat('F Y') }}
    </p>
    <x-social-links :links="$guest->socialLinksVisibleTo()" />
    <ul class="host-booking__facts">
      <li><x-icon name="sparkle" :size="16" /> {{ $booking->experience->title }}</li>
      <li><x-icon name="calendar-blank" :size="16" /> {{ Str::ucfirst($booking->date->localStart()->translatedFormat('D j M · H:i')) }}</li>
      <li><x-icon name="users" :size="16" /> {{ plural_es($booking->guests, 'persona', 'personas') }}</li>
      <li><x-icon name="wallet" :size="16" /> Recibís {{ money($booking->host_payout) }}</li>
    </ul>
    @if ($booking->dietary_needs?->isNotEmpty() || $booking->food_allergies)
      <p class="host-booking__note"><strong>Alimentación:</strong>
        {{ $booking->dietary_needs?->map->needLabel()->join(', ') }}@if ($booking->dietary_needs?->isNotEmpty() && $booking->food_allergies). @endif
        @if ($booking->food_allergies)Alergias: {{ $booking->food_allergies }}@endif
      </p>
    @endif
    @if ($booking->guest_note)
      <p class="host-booking__note"><strong>Mensaje:</strong> {{ $booking->guest_note }}</p>
    @endif
    <p class="hint" style="margin:4px 0 0">Código {{ $booking->code }} · pedida {{ $booking->created_at->diffForHumans() }}</p>
    @if ($pending)
      <div class="host-booking__actions">
        <form method="post" action="{{ route('anfitrion.reservas.confirmar', $booking) }}">@csrf<button class="btn btn--primary btn--sm">Confirmá</button></form>
        <form method="post" action="{{ route('anfitrion.reservas.rechazar', $booking) }}" onsubmit="return confirm(@js('¿Rechazás la reserva de '.Str::before($guest->name.' ', ' ').'? Le avisamos y no se le cobra nada.'))">@csrf<button class="btn btn--tertiary btn--sm">Rechazá</button></form>
      </div>
    @elseif (($closing ?? false) && $booking->canBeMarkedNoShow())
      <div class="host-booking__actions">
        <form method="post" action="{{ route('anfitrion.reservas.no-vino', $booking) }}" onsubmit="return confirm(@js('¿Confirmás que '.Str::before($guest->name.' ', ' ').' no vino ni avisó? No se le devuelve nada y puede pedirnos que lo revisemos.'))">@csrf<button class="btn btn--tertiary btn--sm">No vino</button></form>
      </div>
    @endif
  </div>
</article>
