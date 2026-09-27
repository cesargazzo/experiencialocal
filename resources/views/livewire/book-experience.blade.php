<div class="book" id="reservar">
  @if ($booking)
    <div class="success">
      <div class="success__icon"><x-icon name="check" :size="32" /></div>
      <h3 style="font-size:26px;margin-bottom:8px">Pediste tu lugar</h3>
      <p style="color:var(--tinta-suave);margin:0">Código <strong>{{ $booking->code }}</strong>. Pediste {{ plural_es($booking->guests, 'lugar', 'lugares') }} para <strong>{{ $booking->date->starts_at->translatedFormat('D j M · H:i') }}</strong>. {{ Str::before($experience->host->display_name, ' ') }} confirma dentro de las 24 h y recién ahí se cobra.</p>
      <p class="hint" style="margin-top:14px">Total a pagar al confirmar: <strong>{{ money($booking->total) }}</strong></p>
    </div>
  @elseif ($experience->upcomingDates->isEmpty())
    <div class="book__price">{{ money($experience->price) }} <small>por persona</small></div>
    <p class="hint" style="margin:16px 0 0">No hay fechas abiertas por ahora. Volvé a mirar en unos días.</p>
  @else
    <form wire:submit="submit">
      <div class="book__price">{{ money($experience->price) }} <small>por persona</small></div>

      <div class="field">
        <label for="b-fecha">Fecha</label>
        <select id="b-fecha" wire:model.live="dateId">
          @foreach ($experience->upcomingDates as $d)
            <option value="{{ $d->id }}" @disabled($d->seatsLeft() === 0)>{{ $d->starts_at->translatedFormat('D j M · H:i') }}{{ $d->seatsLeft() === 0 ? ' · completo' : '' }}</option>
          @endforeach
        </select>
        @error('dateId') <span class="error" style="display:block">{{ $message }}</span> @enderror
      </div>

      <div class="field">
        <label for="b-personas">Personas</label>
        <select id="b-personas" wire:model.live="guests">
          @for ($i = 1; $i <= $experience->max_guests; $i++)
            <option value="{{ $i }}">{{ $i }} {{ $i === 1 ? 'persona' : 'personas' }}</option>
          @endfor
        </select>
        @if ($this->selectedDate)
          <span class="hint">{{ $this->selectedDate->seatsLeft() === 0 ? 'Esta fecha está completa.' : 'Quedan '.$this->selectedDate->seatsLeft().' lugares en esta fecha.' }}</span>
        @endif
        @error('guests') <span class="error" style="display:block">{{ $message }}</span> @enderror
      </div>

      <div class="field">
        <label for="b-nota">Mensaje para el anfitrión</label>
        <textarea id="b-nota" rows="2" wire:model="note" placeholder="Alergias, ocasión especial, cómo llegan…"></textarea>
      </div>

      <div class="book__rows">
        <div class="book__row"><span>{{ money($experience->price) }} × {{ $guests }}</span><span>{{ money($this->subtotal) }}</span></div>
        <div class="book__row"><span>Tarifa de servicio ({{ (int) round(\App\Services\BookingService::SERVICE_FEE_RATE * 100) }}%)</span><span>{{ money($this->serviceFee) }}</span></div>
        <div class="book__row book__row--total"><span>Total</span><span>{{ money($this->subtotal + $this->serviceFee) }}</span></div>
      </div>

      <button class="btn btn--primary btn--block" type="submit" wire:loading.attr="disabled">
        <span wire:loading.remove>{{ auth()->check() ? 'Reservá tu lugar' : 'Ingresá para reservar' }}</span>
        <span wire:loading>Enviando…</span>
      </button>
      <p class="hint" style="text-align:center;margin:12px 0 0">No se cobra hasta que el anfitrión confirme.</p>
    </form>
  @endif
</div>
