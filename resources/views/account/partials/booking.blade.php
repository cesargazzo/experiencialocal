@php
  $experience = $booking->experience;
  $start = $booking->date->localStart();
  $badge = match ($booking->status) {
      \App\Enums\BookingStatus::Requested => 'badge--espera',
      \App\Enums\BookingStatus::Confirmed => 'badge--ok',
      \App\Enums\BookingStatus::Completed => 'badge--nivel-2',
      \App\Enums\BookingStatus::Declined, \App\Enums\BookingStatus::Cancelled, \App\Enums\BookingStatus::NoShow => 'badge--error',
      default => 'badge--nivel-1',
  };
@endphp
<article class="booking-item">
  <a class="booking-item__img" href="{{ route('experiencias.show', $experience) }}" style="background-image:url('{{ $experience->coverUrl('card') }}')" aria-hidden="true" tabindex="-1"></a>
  <div>
    <h3 class="booking-item__title"><a href="{{ route('experiencias.show', $experience) }}">{{ $experience->title }}</a></h3>
    <p class="booking-item__meta">
      <span class="badge {{ $badge }}">{{ $booking->status->label() }}</span>
      <span>{{ __('Con :name', ['name' => $experience->host->user->first_name]) }} · {{ $experience->placeLabel() }}</span>
    </p>
    <ul class="booking-item__facts">
      <li><x-icon name="calendar-blank" :size="16" /> {{ Str::ucfirst($start->isoFormat('dddd LL')) }}</li>
      <li><x-icon name="clock" :size="16" /> {{ $start->format('H:i') }} <small>({{ __('hora de :place', ['place' => $experience->province?->name ?? $experience->city]) }})</small></li>
      <li><x-icon name="users" :size="16" /> {{ plural_es($booking->guests, __('persona'), __('personas')) }}</li>
      <li><x-icon name="wallet" :size="16" /> {{ money($booking->total) }} · {{ __('código :code', ['code' => $booking->code]) }}</li>
    </ul>
    @if ($booking->status === \App\Enums\BookingStatus::Confirmed && $experience->meeting_address)
      <p class="host-booking__note" style="margin:10px 0 0"><strong>{{ __('Punto de encuentro:') }}</strong> {{ $experience->meeting_address }}
        @if ($url = $experience->directionsUrl()) · <a href="{{ $url }}" target="_blank" rel="noopener">{{ __('Cómo llegar') }}</a>@endif
      </p>
    @endif
    @if ($booking->status === \App\Enums\BookingStatus::Requested)
      <p class="hint" style="margin:8px 0 0">{{ __('El anfitrión confirma dentro de las 24 h. Recién ahí se cobra.') }}</p>
    @endif
    @if ($booking->status === \App\Enums\BookingStatus::Cancelled && $booking->refund_percent !== null && $booking->confirmed_at)
      <p class="hint" style="margin:8px 0 0">{{ $booking->refund_percent > 0 ? __('Se te devuelve el :percent% de lo que pagaste.', ['percent' => $booking->refund_percent]) : __('Por la política de cancelación no hubo devolución.') }}</p>
    @endif
    @if ($booking->status === \App\Enums\BookingStatus::NoShow)
      <p class="hint" style="margin:8px 0 0">{{ __('El anfitrión marcó que no fuiste, así que no hay devolución.') }} <a href="{{ route('ayuda') }}">{{ __('¿Fuiste o hubo un problema? Escribinos y lo revisamos.') }}</a></p>
    @endif
    @if ($booking->review)
      <p class="host-booking__note" style="margin:10px 0 0"><strong>{{ __('Tu opinión:') }}</strong> <x-stars :rating="$booking->review->rating" /> {{ Str::limit($booking->review->body, 140) }}</p>
    @elseif ($booking->status === \App\Enums\BookingStatus::Completed && $booking->completed_at?->gt(now()->subDays(\App\Http\Controllers\ReviewController::WINDOW_DAYS)))
      <form method="post" action="{{ route('cuenta.reservas.opinion', $booking) }}" class="review-form" id="opinar-{{ $booking->id }}">
        @csrf
        <p style="margin:0 0 6px"><strong>{{ __('¿Cómo te fue?') }}</strong></p>
        <div class="star-input" role="radiogroup" aria-label="{{ __('Calificación') }}">
          @for ($i = 5; $i >= 1; $i--)
            <input type="radio" id="rating-{{ $booking->id }}-{{ $i }}" name="rating" value="{{ $i }}" @checked((int) old('rating') === $i) required>
            <label for="rating-{{ $booking->id }}-{{ $i }}" title="{{ __(':rating de 5', ['rating' => $i]) }}"><x-icon name="star" :size="26" /><span class="sr-only">{{ __(':rating de 5', ['rating' => $i]) }}</span></label>
          @endfor
        </div>
        <textarea name="body" rows="3" maxlength="1500" required placeholder="{{ __('Qué te gustó, cómo te recibieron, qué recomendarías.') }}">{{ old('body') }}</textarea>
        @error('rating')<span class="error" style="display:block">{{ $message }}</span>@enderror
        @error('body')<span class="error" style="display:block">{{ $message }}</span>@enderror
        <button class="btn btn--primary btn--sm" type="submit" style="margin-top:8px">{{ __('Publicá tu opinión') }}</button>
      </form>
    @endif
    @if (in_array($booking->status, [\App\Enums\BookingStatus::Requested, \App\Enums\BookingStatus::Confirmed], true) && $booking->date->starts_at->isFuture())
      @php
        $refundNow = $booking->guestRefundPercentNow();
        $refundText = match (true) {
            $booking->status === \App\Enums\BookingStatus::Requested => __('Todavía no se cobró nada: si cancelás ahora no pagás.'),
            $refundNow === 100 => __('Si cancelás ahora se te devuelve todo.'),
            $refundNow > 0 => __('Si cancelás ahora se te devuelve el :percent%.', ['percent' => $refundNow]),
            default => __('Si cancelás ahora no hay devolución.'),
        };
      @endphp
      <p class="hint" style="margin:10px 0 0">
        <strong>{{ __('Cancelación :policy:', ['policy' => Str::lower($booking->cancellationPolicy()->label())]) }}</strong> {{ $booking->cancellationPolicy()->summary() }}
        <br>{{ $refundText }}
      </p>
      <div class="booking-item__actions" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px">
        @if ($booking->status === \App\Enums\BookingStatus::Confirmed)
          <a class="btn btn--ghost btn--sm" href="{{ route('cuenta.reservas.calendario', $booking) }}"><x-icon name="calendar-blank" :size="16" /> {{ __('Sumala a tu calendario') }}</a>
        @endif
        <form method="post" action="{{ route('cuenta.reservas.cancelar', $booking) }}" onsubmit="return confirm(@js(__('¿Cancelás tu reserva de :title? :refund Le avisamos al anfitrión.', ['title' => $experience->title, 'refund' => $refundText])))">
          @csrf
          <button class="btn btn--ghost btn--sm" type="submit">{{ __('Cancelá la reserva') }}</button>
        </form>
      </div>
    @endif
  </div>
</article>
