<div class="book" id="reservar">
  @if ($booking)
    <div class="success">
      <div class="success__icon"><x-icon name="check" :size="32" /></div>
      <h3 style="font-size:26px;margin-bottom:8px">{{ __('Pediste tu lugar') }}</h3>
      <p style="color:var(--tinta-suave);margin:0">{!! __('Código <strong>:code</strong>. Pediste :places para <strong>:date</strong>.', ['code' => e($booking->code), 'places' => e(plural_es($booking->guests, __('lugar'), __('lugares'))), 'date' => e($booking->date->localStart()->translatedFormat('D j M · H:i'))]) !!} {{ __(':name confirma dentro de las 24 h y recién ahí se cobra.', ['name' => $experience->host->user->first_name]) }}</p>
      <p class="hint" style="margin-top:14px">{{ __('Total a pagar al confirmar:') }} <strong>{{ money($booking->total) }}</strong></p>
    </div>
  @elseif ($experience->upcomingDates->isEmpty())
    <div class="book__price">{{ money($experience->price) }} <small>{{ __('por persona') }}</small></div>
    <p class="hint" style="margin:16px 0 0">{{ __('No hay fechas abiertas por ahora. Volvé a mirar en unos días.') }}</p>
  @else
    <form wire:submit="submit">
      <div class="book__price">{{ money($experience->price) }} <small>{{ __('por persona') }}</small></div>

      <div class="field">
        <label for="b-fecha">{{ __('Fecha') }}</label>
        <span class="hint" style="margin-top:-2px">{{ __('Horarios de :place.', ['place' => $experience->province?->name ?? $experience->city]) }}</span>
        <select id="b-fecha" wire:model.live="dateId">
          @foreach ($experience->upcomingDates as $d)
            <option value="{{ $d->id }}" @disabled($d->seatsLeft() === 0)>{{ $d->localStart()->translatedFormat('D j M · H:i') }}{{ $d->seatsLeft() === 0 ? ' · '.__('completo') : '' }}</option>
          @endforeach
        </select>
        @error('dateId') <span class="error" style="display:block">{{ $message }}</span> @enderror
      </div>

      <div class="field">
        <label for="b-personas">{{ __('Personas') }}</label>
        <select id="b-personas" wire:model.live="guests">
          @for ($i = 1; $i <= $experience->max_guests; $i++)
            <option value="{{ $i }}">{{ $i }} {{ $i === 1 ? __('persona') : __('personas') }}</option>
          @endfor
        </select>
        @if ($this->selectedDate)
          <span class="hint">{{ $this->selectedDate->seatsLeft() === 0 ? __('Esta fecha está completa.') : ($this->selectedDate->seatsLeft() === 1 ? __('Queda 1 lugar en esta fecha.') : __('Quedan :count lugares en esta fecha.', ['count' => $this->selectedDate->seatsLeft()])) }}</span>
        @endif
        @error('guests') <span class="error" style="display:block">{{ $message }}</span> @enderror
      </div>

      @auth
        @if (auth()->user()->required_features?->isNotEmpty())
          <div class="diet-check">
            <strong>{{ __('Lo que necesitás') }}</strong>
            <ul>
              @foreach (auth()->user()->required_features as $need)
                @if ($experience->features?->contains($need))
                  <li class="is-ok"><x-icon name="check" :size="16" /> {{ $need->needLabel() }}: {{ Str::lower($need->label()) }}</li>
                @else
                  <li class="is-missing">{{ __(':need: el anfitrión no lo indica', ['need' => $need->needLabel()]) }}</li>
                @endif
              @endforeach
            </ul>
            <span class="hint">{{ __('Si algo no lo indica, consultale en el mensaje.') }} <a href="{{ route('cuenta.intereses') }}">{{ __('Cambialo') }}</a></span>
          </div>
        @endif
        @if (auth()->user()->hasDietaryNeeds())
          @php($needs = auth()->user()->dietary_needs ?? collect())
          @php($offered = $experience->dietary_options ?? collect())
          <div class="diet-check">
            <strong>{{ __('Tu alimentación') }}</strong>
            <ul>
              @foreach ($needs as $need)
                @if ($need->isCoveredBy($offered))
                  <li class="is-ok"><x-icon name="check" :size="16" /> {{ __(':need: la cubre', ['need' => $need->needLabel()]) }}</li>
                @else
                  <li class="is-missing">{{ __(':need: no la indica', ['need' => $need->needLabel()]) }}</li>
                @endif
              @endforeach
              @if (auth()->user()->food_allergies)
                <li>{{ __('Alergias: :allergies', ['allergies' => auth()->user()->food_allergies]) }}</li>
              @endif
            </ul>
            <span class="hint">{{ __('Se lo pasamos al anfitrión con tu reserva. Si algo no lo cubre, consultale en el mensaje antes de reservar.') }} <a href="{{ route('cuenta.perfil') }}#alimentacion">{{ __('Cambiala') }}</a></span>
          </div>
        @endif
      @endauth

      <div class="field">
        <label for="b-nota">{{ __('Mensaje para el anfitrión') }}</label>
        <textarea id="b-nota" rows="2" wire:model="note" placeholder="{{ __('Alergias, ocasión especial, cómo llegan…') }}"></textarea>
      </div>

      <div class="book__rows">
        <div class="book__row"><span>{{ money($experience->price) }} × {{ $guests }}</span><span>{{ money($this->subtotal) }}</span></div>
        <div class="book__row"><span>{{ __('Tarifa de servicio (:percent%)', ['percent' => (int) round(\App\Services\BookingService::SERVICE_FEE_RATE * 100)]) }}</span><span>{{ money($this->serviceFee) }}</span></div>
        <div class="book__row book__row--total"><span>{{ __('Total') }}</span><span>{{ money($this->subtotal + $this->serviceFee) }}</span></div>
      </div>

      @if ($this->existingBooking)
        <p class="notice" style="margin-bottom:12px">{{ __('Ya reservaste esta fecha (:status, código :code).', ['status' => Str::lower($this->existingBooking->status->label()), 'code' => $this->existingBooking->code]) }} <a href="{{ route('cuenta.reservas') }}">{{ __('Mirá tus reservas') }}</a>.</p>
      @endif
      <button class="btn btn--primary btn--block" type="submit" wire:loading.attr="disabled" @disabled($this->existingBooking)>
        <span wire:loading.remove>{{ auth()->check() ? __('Reservá tu lugar') : __('Ingresá para reservar') }}</span>
        <span wire:loading>{{ __('Enviando…') }}</span>
      </button>
      <p class="hint" style="text-align:center;margin:12px 0 0">{{ __('No se cobra hasta que el anfitrión confirme.') }}</p>
    </form>
  @endif
</div>
