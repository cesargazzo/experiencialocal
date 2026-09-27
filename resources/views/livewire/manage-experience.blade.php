<main class="container wizard" style="max-width:920px">
  <p class="eyebrow"><a href="{{ route('anfitrion.panel') }}">Tu espacio de anfitrión</a></p>
  <h1 class="title">Editá <span class="hl">{{ $experience->title }}</span>.</h1>
  <p style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <span class="badge {{ $experience->status === \App\Enums\ExperienceStatus::Published ? 'badge--ok' : 'badge--espera' }}">{{ $experience->statusLabel() }}</span>
    <a href="{{ route('experiencias.show', $experience) }}">Mirá cómo se ve</a>
  </p>
  @if ($notice)
    <p class="notice notice--ok" role="status">{{ $notice }}</p>
  @endif
  @if ($experience->rejection_reason)
    <p class="notice">No la pudimos publicar. Motivo: {{ $experience->rejection_reason }}. Corregila y guardá: la revisamos de nuevo.</p>
  @endif

  <section class="wizard__panel" id="fechas">
    <h2>Fechas</h2>
    <p>Cargá una fecha suelta o varias de una vez: elegí los días de la semana y el rango. Los horarios son los de {{ $experience->province?->name ?? $experience->city }}.</p>

    @if ($upcomingDates->isEmpty())
      <p class="hint">No tenés fechas próximas: nadie puede reservar hasta que cargues al menos una.</p>
    @else
      <ul class="date-list">
        @foreach ($upcomingDates as $date)
          <li wire:key="date-{{ $date->id }}">
            <span><strong>{{ Str::ucfirst($date->localStart()->translatedFormat('D j M Y')) }}</strong> · {{ $date->localStart()->format('H:i') }}</span>
            <span class="hint">{{ $date->booked_count }}/{{ $date->capacity }} reservados</span>
            @if ($date->booked_count === 0)
              <button type="button" class="btn btn--ghost btn--sm" wire:click="removeDate({{ $date->id }})" wire:confirm="¿Sacamos esta fecha?"><x-icon name="trash" :size="16" /> Sacala</button>
            @endif
          </li>
        @endforeach
      </ul>
    @endif

    <div class="date-add">
      <div class="segmented" role="radiogroup" aria-label="Cómo cargar fechas">
        <label><input type="radio" wire:model.live="date_mode" value="single"> Una fecha</label>
        <label><input type="radio" wire:model.live="date_mode" value="range"> Varias por días de la semana</label>
      </div>
      @if ($date_mode === 'single')
        <div class="grid-2">
          <div class="field"><label for="single_date">Fecha</label><input id="single_date" type="date" wire:model="single_date" min="{{ now($experience->timezone())->toDateString() }}">@error('single_date')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
          <div class="field"><label for="time">Hora</label><input id="time" type="time" wire:model="time">@error('time')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
        </div>
      @else
        <fieldset class="field" style="border:0;padding:0">
          <legend style="font-weight:600">Días</legend>
          <div class="weekday-picker">
            @foreach ($weekdayNames as $iso => $name)
              <label class="choice"><input type="checkbox" wire:model="weekdays" value="{{ $iso }}"> <span>{{ $name }}</span></label>
            @endforeach
          </div>
          @error('weekdays')<span class="error" style="display:block">{{ $message }}</span>@enderror
        </fieldset>
        <div class="grid-3">
          <div class="field"><label for="range_from">Desde</label><input id="range_from" type="date" wire:model="range_from" min="{{ now($experience->timezone())->toDateString() }}">@error('range_from')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
          <div class="field"><label for="range_to">Hasta</label><input id="range_to" type="date" wire:model="range_to">@error('range_to')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
          <div class="field"><label for="time">Hora</label><input id="time" type="time" wire:model="time">@error('time')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
        </div>
      @endif
      @error('dates')<p class="error" style="display:block">{{ $message }}</p>@enderror
      <div class="wizard__actions"><span class="hint">Cada fecha toma {{ plural_es($experience->max_guests, 'cupo', 'cupos') }} y dura {{ $experience->durationLabel() }}.</span><button class="btn btn--secondary" type="button" wire:click="addDates" wire:loading.attr="disabled">Sumá las fechas</button></div>
    </div>
  </section>

  <section class="wizard__panel">
    <h2>Precio, cupos y comida</h2>
    <p>Estos cambios se aplican al momento. Las reservas que ya existen mantienen su precio.</p>
    <div class="grid-3">
      <div class="field"><label for="price">Precio por persona</label><input id="price" type="number" min="1000" step="500" wire:model="price">@error('price')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
      <div class="field"><label for="max_guests">Cupos por fecha</label><input id="max_guests" type="number" min="1" max="50" wire:model="max_guests">@error('max_guests')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
      <div class="field"><label for="duration_hours">Duración (horas)</label><input id="duration_hours" type="number" min="1" max="24" wire:model="duration_hours">@error('duration_hours')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
    </div>
    <fieldset class="field" style="border:0;padding:0">
      <legend style="font-weight:600">Opciones de comida</legend>
      <span class="hint">Marcá solo lo que puedas garantizar.</span>
      <div class="choice-grid">
        @foreach ($dietaryOptions as $option)
          <label class="choice">
            <input type="checkbox" wire:model="dietary_options" value="{{ $option->value }}">
            <span style="flex-direction:column;align-items:flex-start;gap:2px"><strong>{{ $option->label() }}</strong><small class="hint" style="margin:0">{{ $option->hint() }}</small></span>
          </label>
        @endforeach
      </div>
    </fieldset>

    <h2 style="margin-top:32px">Contenido</h2>
    <p>Si cambiás el texto, la categoría o la foto, la revisamos de nuevo @if ($experience->status === \App\Enums\ExperienceStatus::Published)<strong>y deja de verse hasta que la aprobemos</strong>@endif.</p>
    <div class="field"><label for="title">Título</label><input id="title" wire:model="title">@error('title')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
    <div class="grid-2">
      <div class="field"><label for="category_id">Categoría</label><select id="category_id" wire:model="category_id">@foreach ($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>@error('category_id')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
      <div class="field"><label for="type_label">Tipo</label><input id="type_label" wire:model="type_label">@error('type_label')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
    </div>
    <div class="field">
      <label for="cover">Foto</label>
      <div class="upload upload--cover" x-data="{ uploading: false, progress: 0 }" x-on:livewire-upload-start="uploading = true; progress = 0" x-on:livewire-upload-progress="progress = $event.detail.progress" x-on:livewire-upload-finish="uploading = false" x-on:livewire-upload-error="uploading = false">
        <div class="upload__preview">
          @if ($cover && ! $errors->has('cover'))
            <img src="{{ $cover->temporaryUrl() }}" alt="Vista previa de la foto nueva">
          @elseif ($url = $experience->coverUrl('card'))
            <img src="{{ $url }}" alt="Foto actual">
          @else
            <x-icon name="mountains" :size="40" />
          @endif
        </div>
        <div class="upload__body">
          <input id="cover" class="sr-only" type="file" wire:model="cover" accept="image/jpeg,image/png,image/webp">
          <label for="cover" class="btn btn--tertiary btn--sm upload__pick">Cambiá la foto</label>
          <span class="hint">JPG, PNG o WebP de al menos 600 px de cada lado.</span>
          <x-upload-progress />
          @error('cover')<span class="error" style="display:block">{{ $message }}</span>@enderror
        </div>
      </div>
    </div>
    <div class="field"><label for="summary">Resumen (una línea)</label><input id="summary" wire:model="summary" maxlength="200">@error('summary')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
    <div class="field"><label for="description">Descripción</label><textarea id="description" rows="6" wire:model="description"></textarea>@error('description')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
    <div class="wizard__actions"><a class="btn btn--ghost" href="{{ route('anfitrion.panel') }}">Volver</a><button class="btn btn--primary" type="button" wire:click="save" wire:loading.attr="disabled">Guardá los cambios</button></div>
  </section>
</main>
