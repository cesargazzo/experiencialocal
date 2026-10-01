<x-layout :canonical="$activeCategory ? route('home', ['cat' => $activeCategory->slug]) : route('home')" :title="$activeCategory ? __(':category con gente local', ['category' => $activeCategory->name]) : null">
  <section class="hero" id="inicio">
    <div class="container hero__grid">
      <div>
        <p class="eyebrow">{{ __('Tinku significa encuentro') }}</p>
        <h1 class="hero__title">{!! __('Viví el lugar <span class="hl">con su gente</span>.') !!}</h1>
        <p class="hero__lead">{{ __('Comé en la casa de una cocinera de barrio, amasá pasta con una familia italiana o caminá la montaña con quien nació al pie. En Tinku reservás con la gente que vive ahí.') }}</p>
        <div class="hero__actions">
          <a class="btn btn--secondary" href="#experiencias">{{ __('Conocé las experiencias') }} <x-icon name="arrow-right" :size="18" class="icon--arrow" /></a>
          <a class="btn btn--tertiary" href="{{ route('anfitrion.registro') }}">{{ __('Quiero ser anfitrión') }}</a>
        </div>
        <div class="hero__trust">
          <span><x-icon name="seal-check" /> {{ __('Identidad verificada') }}</span>
          <span><x-icon name="lock-key" /> {{ __('Pago dentro de Tinku') }}</span>
          <span><x-icon name="users" /> {{ __('Opiniones de quienes fueron') }}</span>
        </div>
      </div>
      <div class="collage">
        @foreach ($experiences->take(3) as $i => $e)
          <div class="collage__img {{ $i === 0 ? 'collage__img--tall' : '' }}" style="background-image:url('{{ $e->coverUrl($i === 0 ? 'hero' : 'card') }}')"><span><x-icon :name="$e->category->icon" :size="14" /> {{ $e->host->publicName() }}</span></div>
        @endforeach
        @if ($experiences->isNotEmpty())
          @php $top = $experiences->first(); @endphp
          <div class="float-card"><x-avatar :user="$top->host->user" :size="44" /><div><div class="float-card__val">{{ money($top->host->plan->hostPayoutFor((float) $top->price * $top->max_guests)) }}</div><div class="float-card__lbl">{{ __('Lo que recibe :name por una fecha completa', ['name' => $top->host->user->first_name]) }}</div></div></div>
        @endif
      </div>
    </div>
  </section>

  <div class="container">
    <form class="search" method="get" action="{{ route('home') }}#experiencias" role="search">
      <div class="search__field"><label for="search-cat">{{ __('Qué querés hacer') }}</label>
        <select id="search-cat" name="cat"><option value="">{{ __('Todas las experiencias') }}</option>@foreach ($categories as $c)<option value="{{ $c->slug }}" @selected($activeCategory?->id === $c->id)>{{ $c->name }}</option>@endforeach</select></div>
      <div class="search__field"><label for="search-lugar">{{ __('Dónde') }}</label><input id="search-lugar" name="lugar" type="text" value="{{ request('lugar') }}" placeholder="La Rioja, Chilecito…"></div>
      <div class="search__field"><label for="search-fecha">{{ __('Cuándo') }}</label><input id="search-fecha" name="fecha" type="date" min="{{ now(config('tinku.timezone'))->toDateString() }}" value="{{ request('fecha') }}"></div>
      <div class="search__field search__field--narrow"><label for="search-personas">{{ __('Personas') }}</label><input id="search-personas" name="personas" type="number" min="1" max="50" inputmode="numeric" value="{{ request('personas') }}" placeholder="2"></div>
      <div class="search__submit"><button class="btn btn--secondary" type="submit">{{ __('Buscar') }}</button></div>
    </form>
  </div>

  <section class="section section--tight">
    <div class="container stats">
      <div class="stat reveal"><div class="stat__val" data-count="{{ $stats['experiences'] }}">{{ $stats['experiences'] }}</div><div class="stat__lbl">{{ __('experiencias publicadas') }}</div></div>
      <div class="stat reveal"><div class="stat__val" data-count="{{ $stats['reviews'] }}">{{ $stats['reviews'] }}</div><div class="stat__lbl">{{ __('opiniones de quienes fueron') }}</div></div>
      <div class="stat reveal"><div class="stat__val">{{ number_format($stats['rating'], 1, ',', '.') }}</div><div class="stat__lbl">{{ __('puntaje promedio') }}</div></div>
      <div class="stat reveal"><div class="stat__val">24 h</div><div class="stat__lbl">{{ __('para que el anfitrión confirme') }}</div></div>
    </div>
  </section>

  <section class="section" id="experiencias" style="padding-top:16px">
    <div class="container">
      <div class="section-head">
        <div><p class="eyebrow">{{ __('Experiencias') }}</p><h2 class="title">{{ __('Elegí qué hacer.') }} <span class="hl">{{ __('Conocé con quién.') }}</span></h2></div>
        <p class="lead">{{ __('Cada experiencia muestra qué incluye, quién la ofrece, el precio final y las opiniones de quienes fueron.') }}</p>
      </div>
      @if ($myHiddenExperiences->isNotEmpty())
        <p class="notice" style="margin-bottom:20px">
          {{ $myHiddenExperiences->count() === 1 ? __('Tu experiencia') : __('Tus experiencias') }}
          @foreach ($myHiddenExperiences as $mine)<a href="{{ route('experiencias.show', $mine) }}">{{ $mine->title }}</a> ({{ Str::lower($mine->statusLabel()) }})@if (! $loop->last), @endif @endforeach
          {{ $myHiddenExperiences->count() === 1 ? __('todavía no aparece acá: solo se listan las publicadas.') : __('todavía no aparecen acá: solo se listan las publicadas.') }} <a href="{{ route('anfitrion.panel') }}">{{ __('Mirá tu espacio de anfitrión') }}</a>.
        </p>
      @endif
      <div class="chips">
        <a class="chip {{ $activeCategory ? '' : 'is-active' }}" href="{{ route('home', request()->except('cat')) }}#experiencias" @if (! $activeCategory) aria-current="true" @endif>{{ __('Todas') }}</a>
        @foreach ($categories as $c)
          <a class="chip {{ $activeCategory?->id === $c->id ? 'is-active' : '' }}" href="{{ route('home', [...request()->except('cat'), 'cat' => $c->slug]) }}#experiencias" @if ($activeCategory?->id === $c->id) aria-current="true" @endif><x-icon :name="$c->icon" :size="16" /> {{ $c->name }}</a>
        @endforeach
      </div>
      <details class="filters-panel" @if ($search->extraFilterCount()) open @endif>
        <summary>{{ __('Más filtros') }} @if ($count = $search->extraFilterCount())<span class="filters-panel__count">{{ $count }}</span>@endif</summary>
        <form method="get" action="{{ route('home') }}#experiencias">
          @foreach (['cat', 'lugar', 'fecha', 'personas'] as $kept)
            @if (request()->filled($kept))<input type="hidden" name="{{ $kept }}" value="{{ request($kept) }}">@endif
          @endforeach
          <div class="filters-panel__grid">
            <fieldset>
              <legend>{{ __('Comida') }}</legend>
              @foreach (\App\Enums\DietaryOption::cases() as $option)
                <label class="check"><input type="checkbox" name="comida[]" value="{{ $option->value }}" @checked(in_array($option->value, $search->selected('comida'), true))> {{ $option->label() }}</label>
              @endforeach
            </fieldset>
            <fieldset>
              <legend>{{ __('Necesito') }}</legend>
              @foreach (\App\Enums\ExperienceFeature::cases() as $feature)
                <label class="check"><input type="checkbox" name="necesito[]" value="{{ $feature->value }}" @checked(in_array($feature->value, $search->selected('necesito'), true))> {{ $feature->label() }}</label>
              @endforeach
            </fieldset>
            <fieldset>
              <legend>{{ __('Dificultad') }}</legend>
              @foreach (\App\Enums\Difficulty::cases() as $level)
                <label class="check"><input type="checkbox" name="dificultad[]" value="{{ $level->value }}" @checked(in_array($level->value, $search->selected('dificultad'), true))> {{ $level->label() }}</label>
              @endforeach
            </fieldset>
            <fieldset>
              <legend>{{ __('Precio y orden') }}</legend>
              <div class="field"><label for="precio_max">{{ __('Hasta (por persona)') }}</label><input id="precio_max" name="precio_max" type="number" min="1" step="1000" inputmode="numeric" value="{{ $search->filters['precio_max'] ?? '' }}" placeholder="$ 50.000"></div>
              <div class="field"><label for="orden">{{ __('Ordenar por') }}</label>
                <select id="orden" name="orden">@foreach (\App\Support\ExperienceSearch::SORTS as $key => $label)<option value="{{ $key }}" @selected(($search->filters['orden'] ?? 'recomendadas') === $key)>{{ __($label) }}</option>@endforeach</select>
              </div>
            </fieldset>
          </div>
          <div class="filters-panel__actions">
            <a class="btn btn--ghost btn--sm" href="{{ route('home', request()->only(['cat', 'lugar', 'fecha', 'personas'])) }}#experiencias">{{ __('Sacá los filtros') }}</a>
            <button class="btn btn--secondary btn--sm" type="submit">{{ __('Aplicá los filtros') }}</button>
          </div>
        </form>
      </details>
      <div x-data="{ view: 'lista' }">
      <div class="results-bar">
      <p class="hint results-count" role="status">{{ plural_es($experiences->count(), __('experiencia'), __('experiencias')) }}@if ($search->filters || $activeCategory) {{ __('con esos filtros') }} · <a href="{{ route('home') }}#experiencias">{{ __('Ver todas') }}</a>@endif</p>
        @if ($mapItems->isNotEmpty())
          <div class="view-toggle" role="group" aria-label="{{ __('Cómo ver los resultados') }}">
            <button type="button" class="chip" x-bind:class="view === 'lista' && 'is-active'" x-bind:aria-pressed="view === 'lista'" x-on:click="view = 'lista'"><x-icon name="list" :size="16" /> {{ __('Lista') }}</button>
            <button type="button" class="chip" x-bind:class="view === 'mapa' && 'is-active'" x-bind:aria-pressed="view === 'mapa'" x-on:click="view = 'mapa'; $nextTick(() => $dispatch('show-map'))"><x-icon name="map-pin" :size="16" /> {{ __('Mapa') }}</button>
          </div>
        @endif
      </div>
      @if ($mapItems->isNotEmpty())
        <div x-show="view === 'mapa'" x-cloak>
          <div wire:ignore class="map map--results" role="region" aria-label="{{ __('Mapa de experiencias') }}"
            x-data="tinkuExperiencesMap({ config: @js(config('tinku.maps')), items: @js($mapItems) })" x-on:show-map.window="show()"></div>
          <p class="hint" style="margin-top:8px">{{ __('Cada punto es la zona aproximada: la dirección exacta se comparte con la reserva confirmada.') }}@if ($mapItems->count() < $experiences->count()) {{ plural_es($experiences->count() - $mapItems->count(), __('experiencia todavía no tiene punto en el mapa.'), __('experiencias todavía no tienen punto en el mapa.')) }}@endif</p>
        </div>
      @endif
      <div class="grid" x-show="view === 'lista'">
        @forelse ($experiences as $e)
          <x-experience-card :experience="$e" />
        @empty
          <div class="empty">{{ __('Todavía no hay experiencias con ese filtro. Si sabés hacer algo así, publicalo vos.') }}</div>
        @endforelse
      </div>
      </div>
    </div>
  </section>

  <section class="section section--alt" id="tipos">
    <div class="container">
      <p class="eyebrow">{{ __('Qué podés ofrecer') }}</p>
      <h2 class="title">{{ __('No hace falta un local. Hace falta algo que sepas hacer bien.') }}</h2>
      <div class="bento" style="margin-top:40px">
        <div class="bento__item bento__item--wide bento__item--tall reveal" style="background-image:url('https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&w=1200&q=70')"><div class="bento__icon"><x-icon name="fork-knife" :size="24" /></div><h3>{{ __('Comida en tu casa') }}</h3><p>{{ __('Una cena, un almuerzo de domingo, un asado. Vos ponés la mesa y la receta.') }}</p></div>
        <div class="bento__item reveal" style="background-image:url('https://images.unsplash.com/photo-1556910103-1c02745aae4d?auto=format&fit=crop&w=700&q=70')"><div class="bento__icon"><x-icon name="cooking-pot" :size="24" /></div><h3>{{ __('Clases de cocina') }}</h3><p>{{ __('Cocinan con vos, comen y se llevan la receta.') }}</p></div>
        <div class="bento__item reveal" style="background-image:url('https://images.unsplash.com/photo-1501555088652-021faa106b9b?auto=format&fit=crop&w=700&q=70')"><div class="bento__icon"><x-icon name="mountains" :size="24" /></div><h3>{{ __('Paseos y viajes') }}</h3><p>{{ __('Los lugares que conoce quien vive ahí.') }}</p></div>
        <div class="bento__item reveal" style="background-image:url('https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=700&q=70')"><div class="bento__icon"><x-icon name="yarn" :size="24" /></div><h3>{{ __('Talleres') }}</h3><p>{{ __('Pan, telar, cerámica, huerta, vino.') }}</p></div>
        <div class="bento__item bento__item--solid reveal"><div class="bento__icon"><x-icon name="sparkle" :size="24" /></div><h3>{{ __('Otra cosa que sepas hacer') }}</h3><p>{{ __('Si se comparte en una tarde, tiene lugar en Tinku.') }}</p></div>
      </div>
    </div>
  </section>

  @if ($plans->isNotEmpty())
  <section class="section section--dark" id="como-ganas">
    <div class="container split" x-data="{ price: 40000, rate: {{ (float) $plans->first()->commission_rate }}, plan: '{{ $plans->first()->name }}', fmt(n) { return '$ ' + Math.round(n).toLocaleString('es-AR'); } }">
      <div>
        <p class="eyebrow">{{ __('Ingresos para el anfitrión') }}</p>
        <h2 class="title">{{ __('Tinku gana solo cuando vos recibís una reserva.') }}</h2>
        <p class="lead">{{ __('Vos definís el precio y los cupos. Quien reserva paga dentro de Tinku. Descontamos la comisión de tu plan y te pasamos el resto después de la experiencia.') }}</p>
        <p class="lead">{{ __('Mové el precio y cambiá de plan para ver cuánto recibís por persona.') }}</p>
      </div>
      <div class="calc">
        <div class="calc__label"><label for="calc-precio">{{ __('Precio por persona') }}</label> <span x-text="fmt(price)"></span></div>
        <input id="calc-precio" type="range" min="10000" max="120000" step="1000" x-model.number="price" :style="`--pct:${(price - 10000) / 1100}%`">
        <div class="calc__plans" role="group" aria-label="{{ __('Plan') }}">
          @foreach ($plans as $p)
            <button type="button" :class="{ 'is-active': plan === '{{ $p->name }}' }" :aria-pressed="plan === '{{ $p->name }}'" @click="plan = '{{ $p->name }}'; rate = {{ (float) $p->commission_rate }}">{{ $p->name }} {{ $p->commissionPercent() }}%</button>
          @endforeach
        </div>
        <div class="calc__row"><span>{{ __('Precio cobrado') }}</span><b x-text="fmt(price)"></b></div>
        <div class="calc__row"><span>{{ __('Comisión de Tinku') }} (<span x-text="Math.round(rate * 100) + '%'"></span>) · {{ __('plan') }} <span x-text="plan"></span></span><b x-text="fmt(price * rate)"></b></div>
        <div class="calc__row calc__row--total"><span>{{ __('Recibís vos*') }}</span><span x-text="fmt(price - price * rate)"></span></div>
        <p class="calc__note">{{ __('*Antes de impuestos, retenciones, costos de cobro y gastos propios.') }}</p>
      </div>
    </div>
  </section>

  <section class="section" id="planes">
    <div class="container">
      <div class="section-head">
        <div><p class="eyebrow">{{ __('Planes para anfitriones') }}</p><h2 class="title">{{ __('Empezá gratis.') }} <span class="hl">{{ __('Crecé cuando quieras.') }}</span></h2></div>
        <p class="lead">{{ __('La comisión baja en los planes pagos. La suscripción conviene cuando recibís más reservas.') }}</p>
      </div>
      <div class="plans">
        @foreach ($plans as $p)
          <article class="plan reveal {{ $p->is_featured ? 'plan--featured' : '' }}">
            @if ($p->is_featured)<span class="plan__badge">{{ __('Recomendado') }}</span>@endif
            <div class="plan__kind">{{ $p->tagline }}</div>
            <h3 class="plan__name">{{ $p->name }}</h3>
            <div class="plan__price">{{ money($p->monthly_price) }}<small>{{ __('por mes') }}</small></div>
            <div class="plan__fee">{{ __(':percent% por reserva', ['percent' => $p->commissionPercent()]) }}</div>
            <ul>@foreach ($p->features ?? [] as $f)<li><x-icon name="check" :size="18" /> {{ $f }}</li>@endforeach</ul>
            <a class="btn {{ $p->is_featured ? 'btn--secondary' : 'btn--tertiary' }}" href="{{ route('anfitrion.registro', ['plan' => $p->slug]) }}">{{ __('Elegí :plan', ['plan' => $p->name]) }}</a>
          </article>
        @endforeach
      </div>
    </div>
  </section>
  @endif

  @if ($testimonials->isNotEmpty())
  <section class="section section--alt">
    <div class="container">
      <p class="eyebrow">{{ __('Lo que dicen') }}</p>
      <h2 class="title">{{ __('Quienes ya fueron.') }}</h2>
      <div class="quotes" style="margin-top:40px">
        @foreach ($testimonials as $t)
          <figure class="quote reveal">
            <p>“{{ $t->body }}”</p>
            <footer><x-avatar :user="$t->user" :size="40" /><span><strong>{{ $t->user->publicName() }}</strong><br>{{ __('Fue a :experience', ['experience' => $t->experience->title]) }}</span></footer>
          </figure>
        @endforeach
      </div>
    </div>
  </section>
  @endif

  <section class="section" id="camino">
    <div class="container">
      <p class="eyebrow">{{ __('Camino del anfitrión') }}</p>
      <h2 class="title">{{ __('De tu idea a tu primera reserva.') }}</h2>
      <div class="steps" style="margin-top:40px">
        <div class="step reveal"><div class="step__num">01</div><h3 class="step__title">{{ __('Registrate') }}</h3><p>{{ __('Creá tu cuenta y confirmá tu email y tu teléfono.') }}</p></div>
        <div class="step reveal"><div class="step__num">02</div><h3 class="step__title">{{ __('Verificá') }}</h3><p>{{ __('Validá tu documento, tu identidad y el lugar donde recibís.') }}</p></div>
        <div class="step reveal"><div class="step__num">03</div><h3 class="step__title">{{ __('Publicá') }}</h3><p>{{ __('Contá qué incluye y definí precio, fechas, cupos y condiciones.') }}</p></div>
        <div class="step reveal"><div class="step__num">04</div><h3 class="step__title">{{ __('Recibí') }}</h3><p>{{ __('Confirmá cada reserva y construí tu reputación.') }}</p></div>
      </div>
      <div class="cta reveal">
        <div><h2>{{ __('Tu mesa, tu cocina o tu pueblo pueden ser una experiencia.') }}</h2><p>{{ __('Publicar es gratis. Cobrás cuando recibís.') }}</p></div>
        <a class="btn btn--primary" href="{{ route('anfitrion.registro') }}">{{ __('Empezá tu registro') }} <x-icon name="arrow-right" :size="18" class="icon--arrow" /></a>
      </div>
    </div>
  </section>
</x-layout>
