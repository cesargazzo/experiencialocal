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
      <span>Con {{ $experience->host->user->first_name }} · {{ $experience->placeLabel() }}</span>
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
    @if ($booking->review)
      <p class="host-booking__note" style="margin:10px 0 0"><strong>Tu opinión:</strong> <x-stars :rating="$booking->review->rating" /> {{ Str::limit($booking->review->body, 140) }}</p>
    @elseif ($booking->status === \App\Enums\BookingStatus::Completed && $booking->completed_at?->gt(now()->subDays(\App\Http\Controllers\ReviewController::WINDOW_DAYS)))
      <form method="post" action="{{ route('cuenta.reservas.opinion', $booking) }}" class="review-form" id="opinar-{{ $booking->id }}">
        @csrf
        <p style="margin:0 0 6px"><strong>¿Cómo te fue?</strong></p>
        <div class="star-input" role="radiogroup" aria-label="Calificación">
          @for ($i = 5; $i >= 1; $i--)
            <input type="radio" id="rating-{{ $booking->id }}-{{ $i }}" name="rating" value="{{ $i }}" @checked((int) old('rating') === $i) required>
            <label for="rating-{{ $booking->id }}-{{ $i }}" title="{{ $i }} de 5"><x-icon name="star" :size="26" /><span class="sr-only">{{ $i }} de 5</span></label>
          @endfor
        </div>
        <textarea name="body" rows="3" maxlength="1500" required placeholder="Qué te gustó, cómo te recibieron, qué recomendarías.">{{ old('body') }}</textarea>
        @error('rating')<span class="error" style="display:block">{{ $message }}</span>@enderror
        @error('body')<span class="error" style="display:block">{{ $message }}</span>@enderror
        <button class="btn btn--primary btn--sm" type="submit" style="margin-top:8px">Publicá tu opinión</button>
      </form>
    @endif
    @if (in_array($booking->status, [\App\Enums\BookingStatus::Requested, \App\Enums\BookingStatus::Confirmed], true) && $booking->date->starts_at->isFuture())
      <form method="post" action="{{ route('cuenta.reservas.cancelar', $booking) }}" style="margin-top:10px" onsubmit="return confirm('¿Cancelás tu reserva de {{ $experience->title }}? Le avisamos al anfitrión.')">
        @csrf
        <button class="btn btn--ghost btn--sm" type="submit">Cancelá la reserva</button>
      </form>
    @endif
  </div>
</article>
