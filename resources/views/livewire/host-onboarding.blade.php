<main class="container wizard">
  <p class="eyebrow">Camino del anfitrión</p>
  <h1 class="title">Publicá tu primera <span class="hl">experiencia</span>.</h1>
  <div class="wizard__progress">
    @foreach (['01 · Tu perfil', '02 · Tu experiencia', '03 · Revisá', '04 · Publicada'] as $i => $label)
      <div data-label="{{ $label }}" @class(['is-done' => $step > $i + 1, 'is-active' => $step === $i + 1])></div>
    @endforeach
  </div>

  @if ($step === 1)
    <section class="wizard__panel">
      <h2>Tu perfil de anfitrión</h2>
      <p>Tu identidad ya está validada con nivel <strong>{{ auth()->user()->verification_level->label() }}</strong> <x-verification-badge :level="auth()->user()->verification_level" />. Ahora contanos cómo querés mostrarte.</p>
      <div class="grid-2">
        <div class="field"><label>Nombre público</label><input wire:model="display_name">@error('display_name')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
        <div class="field"><label>Plan</label>
          <select wire:model="plan">@foreach ($plans as $p)<option value="{{ $p->slug }}">{{ $p->name }} · {{ money($p->monthly_price) }} por mes · {{ $p->commissionPercent() }}% por reserva</option>@endforeach</select>
        </div>
        <div class="field"><label>Ciudad</label><input wire:model="city" placeholder="La Rioja">@error('city')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
        <div class="field"><label for="province_id">Provincia</label>
          <select id="province_id" wire:model="province_id">
            <option value="">Elegí una</option>
            @foreach ($countries as $country)
              <optgroup label="{{ $country->name }}">
                @foreach ($country->provinces as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
              </optgroup>
            @endforeach
          </select>
          @error('province_id')<span class="error" style="display:block">{{ $message }}</span>@enderror
        </div>
      </div>
      <div class="field"><label>Dirección donde recibís</label><input wire:model="address" placeholder="Calle y número. Solo la ven quienes tienen reserva confirmada.">@error('address')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
      <div class="field">
        <label for="avatar">Tu foto</label>
        <div class="upload">
          <div class="upload__preview">
            @if ($avatar && ! $errors->has('avatar'))
              <img src="{{ $avatar->temporaryUrl() }}" alt="Vista previa de tu foto">
            @elseif (auth()->user()->avatar)
              <img src="{{ auth()->user()->avatar->url('md') }}" alt="Tu foto actual">
            @else
              <x-icon name="user-circle" :size="40" />
            @endif
          </div>
          <div class="upload__body">
            <input id="avatar" type="file" wire:model="avatar" accept="image/jpeg,image/png,image/webp">
            <span class="hint">Una foto tuya, de frente y con buena luz. JPG, PNG o WebP de al menos 200 × 200 px.</span>
            <span class="hint" wire:loading wire:target="avatar">Subiendo…</span>
            @error('avatar')<span class="error" style="display:block">{{ $message }}</span>@enderror
          </div>
        </div>
      </div>
      <div class="field"><label>Contanos sobre vos</label><textarea rows="3" wire:model="bio" placeholder="Qué cocinás, qué lugares conocés, por qué querés recibir gente."></textarea>@error('bio')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
      <div class="wizard__actions"><a class="btn btn--ghost" href="{{ route('home') }}">Cancelar</a><button class="btn btn--secondary" type="button" wire:click="next">Continuá <x-icon name="arrow-right" :size="18" class="icon--arrow" /></button></div>
    </section>

  @elseif ($step === 2)
    <section class="wizard__panel">
      <h2>Tu primera experiencia</h2>
      <p>Definí qué incluye, precio, fecha y cupos. Después podés agregar más fechas.</p>
      <div class="field"><label>Título</label><input wire:model="title" placeholder="Cociná empanadas con Marta en su patio">@error('title')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
      <div class="grid-2">
        <div class="field"><label>Categoría</label><select wire:model="category_id"><option value="">Elegí una</option>@foreach ($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>@error('category_id')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
        <div class="field"><label>Tipo</label><input wire:model="type_label" placeholder="Cocina regional, Paseo de medio día…">@error('type_label')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
        <div class="field"><label>Precio por persona</label><input type="number" min="1000" step="500" wire:model.live="price" placeholder="40000">
          @php $p = $plans->firstWhere('slug', $plan); @endphp
          <span class="hint">{{ $price ? 'Con el plan '.$p->name.' recibís '.money($p->hostPayoutFor($price)).' por persona.' : 'Definí un precio para ver cuánto recibís.' }}</span>
          @error('price')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
        <div class="field"><label>Cupos por fecha</label><input type="number" min="1" max="50" wire:model="max_guests" placeholder="8">@error('max_guests')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
        <div class="field"><label>Duración (horas)</label><input type="number" min="1" max="24" wire:model="duration_hours">@error('duration_hours')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
        <div class="field"><label>Primera fecha</label><input type="date" wire:model="first_date">@error('first_date')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
        <div class="field"><label>Hora</label><input type="time" wire:model="first_time"><span class="hint">En la hora del lugar donde recibís.</span></div>
      </div>
      <div class="field">
        <label for="cover">Foto de la experiencia</label>
        <div class="upload upload--cover">
          <div class="upload__preview">
            @if ($cover && ! $errors->has('cover'))
              <img src="{{ $cover->temporaryUrl() }}" alt="Vista previa de la foto">
            @else
              <x-icon name="mountains" :size="40" />
            @endif
          </div>
          <div class="upload__body">
            <input id="cover" type="file" wire:model="cover" accept="image/jpeg,image/png,image/webp">
            <span class="hint">Mostrá a las personas haciendo algo, con luz natural y en tu lugar. Nada de fotos de stock. Al menos 1200 × 800 px.</span>
            <span class="hint" wire:loading wire:target="cover">Subiendo…</span>
            @error('cover')<span class="error" style="display:block">{{ $message }}</span>@enderror
          </div>
        </div>
      </div>
      <div class="field"><label>Resumen (una línea)</label><input wire:model="summary" maxlength="200" placeholder="Empanadas, cabrito y sobremesa con recetas de familia.">@error('summary')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
      <div class="field"><label>Descripción</label><textarea rows="4" wire:model="description" placeholder="Qué van a vivir, qué incluye, qué tienen que traer."></textarea>@error('description')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
      <div class="wizard__actions"><button class="btn btn--ghost" type="button" wire:click="back">Volver</button><button class="btn btn--secondary" type="button" wire:click="next">Revisá <x-icon name="arrow-right" :size="18" class="icon--arrow" /></button></div>
    </section>

  @elseif ($step === 3)
    @php $p = $plans->firstWhere('slug', $plan); $cat = $categories->firstWhere('id', $category_id); @endphp
    <section class="wizard__panel">
      <h2>Revisá y publicá</h2>
      <p>Así va a verse tu experiencia. Podés volver a editar cualquier paso.</p>
      @if ($cover)
        <img src="{{ $cover->temporaryUrl() }}" alt="Foto de la experiencia" style="width:100%;aspect-ratio:16/9;object-fit:cover;border-radius:var(--radio-md);margin:16px 0 8px">
      @endif
      <div class="summary">
        <div><span>Anfitrión</span><strong>{{ $display_name }} · {{ $city }}</strong></div>
        <div><span>Experiencia</span><strong>{{ $title }}</strong></div>
        <div><span>Categoría</span><strong>{{ $cat?->name }}</strong></div>
        <div><span>Precio por persona</span><strong>{{ money($price ?? 0) }}</strong></div>
        <div><span>Cupos</span><strong>{{ $max_guests }} personas</strong></div>
        <div><span>Primera fecha</span><strong>{{ \Carbon\Carbon::parse($first_date.' '.$first_time)->translatedFormat('D j M · H:i') }}</strong></div>
        <div><span>Plan</span><strong>{{ $p->name }} · {{ $p->commissionPercent() }}% por reserva</strong></div>
        <div><span>Recibís por persona*</span><strong>{{ money($p->hostPayoutFor($price ?? 0)) }}</strong></div>
      </div>
      <p class="hint">*Antes de impuestos, retenciones, costos de cobro y gastos propios.</p>
      @unless (auth()->user()->hasVerificationLevel(\App\Enums\VerificationLevel::Residence))
        <p class="notice">Tu perfil queda <strong>en revisión</strong> hasta que valides tu domicilio (nivel 3). Podés hacerlo desde <a href="{{ route('verificacion') }}">tu centro de verificación</a>.</p>
      @endunless
      @error('title')<span class="error" style="display:block">{{ $message }}</span>@enderror
      <div class="wizard__actions"><button class="btn btn--ghost" type="button" wire:click="back">Volver</button><button class="btn btn--primary" type="button" wire:click="publish" wire:loading.attr="disabled">Publicá tu experiencia</button></div>
    </section>

  @else
    <section class="wizard__panel">
      <div class="success">
        <div class="success__icon"><x-icon name="check" :size="32" /></div>
        <h2>{{ $created?->status === \App\Enums\ExperienceStatus::Published ? 'Tu experiencia está publicada' : 'Tu experiencia quedó en revisión' }}</h2>
        <p style="max-width:44ch;margin:8px auto 24px;color:var(--tinta-suave)">
          @if ($created?->status === \App\Enums\ExperienceStatus::Published)
            Cuando alguien reserve, te avisamos. Confirmás la reserva, recibís a tus invitados y el saldo se liquida después de la experiencia.
          @else
            Un administrador la revisa junto con tu verificación de domicilio. Cuando esté aprobada, se publica sola.
          @endif
        </p>
        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
          @if ($created)<a class="btn btn--secondary" href="{{ route('experiencias.show', $created) }}">Mirá tu experiencia</a>@endif
          <a class="btn btn--tertiary" href="{{ route('home') }}">Volvé al inicio</a>
        </div>
      </div>
    </section>
  @endif
</main>
