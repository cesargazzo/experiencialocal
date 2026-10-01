<main class="container wizard" style="max-width:1100px">
  <p class="eyebrow">Anfitrión</p>
  <h1 class="title">Tu <span class="hl">calendario</span>.</h1>
  @include('host.partials.nav', ['activeHostNav' => 'anfitrion.calendario'])

  @if ($notice)<p class="notice notice--ok" role="status">{{ $notice }}</p>@endif

  <section class="wizard__panel">
    <div class="calendar__head">
      <button type="button" class="calendar__arrow" wire:click="previousMonth" aria-label="Mes anterior">‹</button>
      <h2>{{ $monthLabel }}</h2>
      <button type="button" class="calendar__arrow" wire:click="nextMonth" aria-label="Mes siguiente">›</button>
    </div>

    <div class="calendar" role="grid" aria-label="Fechas de {{ $monthLabel }}">
      <div class="calendar__row calendar__row--names" role="row">
        @foreach ($weekdayNames as $name)<span role="columnheader">{{ $name }}</span>@endforeach
      </div>
      @foreach ($days as $week)
        <div class="calendar__row" role="row">
          @foreach ($week as $day)
            <div @class(['calendar__day', 'is-other' => ! $day['inMonth'], 'is-today' => $day['isToday']]) role="gridcell">
              <span class="calendar__num">{{ $day['date']->day }}</span>
              @foreach ($day['items'] as $date)
                <button type="button" wire:click="select({{ $date->id }})"
                  @class(['calendar__item', 'is-selected' => $selected?->id === $date->id, 'is-closed' => $date->status !== 'open', 'is-full' => $date->seatsLeft() === 0, 'has-pending' => $date->pending_count > 0])>
                  <strong>{{ $date->localStart()->format('H:i') }}</strong> {{ Str::limit($date->experience->title, 22) }}
                  <small>{{ $date->booked_count }}/{{ $date->capacity }}@if ($date->pending_count) · {{ $date->pending_count }} por confirmar @endif @if ($date->status !== 'open') · cerrada @endif</small>
                </button>
              @endforeach
            </div>
          @endforeach
        </div>
      @endforeach
    </div>
    <p class="hint calendar__legend">
      <span class="dot dot--open"></span> Abierta · <span class="dot dot--pending"></span> Con pedidos por confirmar · <span class="dot dot--full"></span> Completa · <span class="dot dot--closed"></span> Venta cerrada
    </p>
  </section>

  @if ($selected)
    <section class="wizard__panel" id="fecha">
      <h2>{{ $selected->experience->title }}</h2>
      <p class="hint" style="margin-top:-8px">{{ Str::ucfirst($selected->localStart()->translatedFormat('l j \d\e F · H:i')) }} · {{ $selected->booked_count }} de {{ $selected->capacity }} lugares ocupados</p>

      @if ($selected->bookings->isNotEmpty())
        <ul class="calendar__bookings">
          @foreach ($selected->bookings as $booking)
            <li><strong>{{ $booking->user->first_name }}</strong> · {{ plural_es($booking->guests, 'persona', 'personas') }} · <span class="badge {{ $booking->status === \App\Enums\BookingStatus::Requested ? 'badge--sev-warning' : 'badge--ok' }}">{{ $booking->status->label() }}</span></li>
          @endforeach
        </ul>
        @if ($selected->pending_count)<p><a href="{{ route('anfitrion.panel') }}#reservas">Confirmá o rechazá los pedidos en tu panel</a>.</p>@endif
      @else
        <p class="hint">Todavía no hay reservas para esta fecha.</p>
      @endif

      @if ($selected->starts_at->isFuture())
        <div class="grid-2" style="margin-top:16px">
          <form wire:submit="saveCapacity" class="subform">
            <div class="field"><label for="capacity">Cupo</label><input id="capacity" type="number" min="1" max="200" wire:model="capacity" inputmode="numeric">@error('capacity')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
            <button class="btn btn--tertiary btn--sm" type="submit" style="margin-top:8px">Guardá el cupo</button>
          </form>
          <div class="subform">
            <p style="margin:0 0 8px">{{ $selected->status === 'open' ? 'La venta está abierta.' : 'La venta está cerrada: nadie más puede reservar.' }}</p>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
              <button type="button" class="btn btn--tertiary btn--sm" wire:click="toggleSales">{{ $selected->status === 'open' ? 'Cerrá la venta' : 'Abrí la venta' }}</button>
              <button type="button" class="btn btn--ghost btn--sm" wire:click="remove" wire:confirm="¿Sacamos esta fecha?" @disabled($selected->booked_count > 0)>Sacá la fecha</button>
            </div>
            @error('remove')<span class="error" style="display:block">{{ $message }}</span>@enderror
          </div>
        </div>
      @endif
    </section>
  @endif

  <section class="wizard__panel">
    <h2>Sumá fechas</h2>
    <p>Las fechas se cargan en cada experiencia: una suelta o varias de una vez, eligiendo días de la semana dentro de un rango (por ejemplo, todos los sábados de marzo a junio).</p>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      @forelse ($experiences as $experience)
        <a class="btn btn--tertiary btn--sm" href="{{ route('anfitrion.experiencias.editar', $experience) }}#fechas"><x-icon name="calendar-blank" :size="16" /> {{ $experience->title }}</a>
      @empty
        <p class="hint">Todavía no tenés experiencias.</p>
      @endforelse
    </div>
  </section>
</main>
