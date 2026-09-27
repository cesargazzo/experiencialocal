@php
  $experience = $booking->experience;
  $start = $booking->date->localStart();
  $badge = match ($booking->status) {
      \App\Enums\BookingStatus::Requested => 'badge--espera',
      \App\Enums\BookingStatus::Confirmed => 'badge--ok',
      \App\Enums\BookingStatus::Completed => 'badge--nivel-2',
      \App\Enums\BookingStatus::Declined, \App\Enums\BookingStatus::Cancelled => 'badge--error',
      default => 'badge--nivel-1',
  };
@endphp
<article class="booking-item">
  <a class="booking-item__img" href="{{ route('experiencias.show', $experience) }}" style="background-image:url('{{ $experience->coverUrl('card') }}')" aria-hidden="true" tabindex="-1"></a>
  <div>
    <h3 class="booking-item__title"><a href="{{ route('experiencias.show', $experience) }}">{{ $experience->title }}</a></h3>
    <p class="booking-item__meta">
      <span class="badge {{ $badge }}">{{ $booking->status->label() }}</span>
      <span>Con {{ Str::before($experience->host->display_name.' ', ' ') }} · {{ $experience->placeLabel() }}</span>
    </p>
    <ul class="booking-item__facts">
      <li><x-icon name="calendar-blank" :size="16" /> {{ Str::ucfirst($start->translatedFormat('l j \d\e F \d\e Y')) }}</li>
      <li><x-icon name="clock" :size="16" /> {{ $start->format('H:i') }} <small>(hora de {{ $experience->province?->name ?? $experience->city }})</small></li>
      <li><x-icon name="users" :size="16" /> {{ plural_es($booking->guests, 'persona', 'personas') }}</li>
      <li><x-icon name="wallet" :size="16" /> {{ money($booking->total) }} · código {{ $booking->code }}</li>
    </ul>
    @if ($booking->status === \App\Enums\BookingStatus::Confirmed && $experience->meeting_address)
      <p class="host-booking__note" style="margin:10px 0 0"><strong>Punto de encuentro:</strong> {{ $experience->meeting_address }}
        @if ($url = $experience->directionsUrl()) · <a href="{{ $url }}" target="_blank" rel="noopener">Cómo llegar</a>@endif
      </p>
    @endif
    @if ($booking->status === \App\Enums\BookingStatus::Requested)
      <p class="hint" style="margin:8px 0 0">El anfitrión confirma dentro de las 24 h. Recién ahí se cobra.</p>
    @endif
    @if (in_array($booking->status, [\App\Enums\BookingStatus::Requested, \App\Enums\BookingStatus::Confirmed], true) && $booking->date->starts_at->isFuture())
      <form method="post" action="{{ route('cuenta.reservas.cancelar', $booking) }}" style="margin-top:10px" onsubmit="return confirm('¿Cancelás tu reserva de {{ $experience->title }}? Le avisamos al anfitrión.')">
        @csrf
        <button class="btn btn--ghost btn--sm" type="submit">Cancelá la reserva</button>
      </form>
    @endif
  </div>
</article>
