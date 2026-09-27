<x-layout>
  <section class="hero" id="inicio">
    <div class="container hero__grid">
      <div>
        <p class="eyebrow">Tinku · encuentro, en quechua</p>
        <h1 class="hero__title">Vivilo con alguien <span class="hl">de acá</span>.</h1>
        <p class="hero__lead">Una cena en casa de una cocinera de barrio, una clase de pastas con una nonna, un paseo guiado por quien nació ahí. Tinku conecta personas locales con quienes quieren conocerlas.</p>
        <div class="hero__actions">
          <a class="btn btn--primary" href="#experiencias">Explorar experiencias <span class="arrow">→</span></a>
          <a class="btn btn--light" href="{{ route('anfitrion.registro') }}">Quiero ser anfitrión</a>
        </div>
        <div class="hero__trust"><span>Identidad verificada</span><span>Pago protegido</span><span>Opiniones de quienes fueron</span></div>
      </div>
      <div class="collage">
        @foreach ($experiences->take(3) as $i => $e)
          <div class="collage__img {{ $i === 0 ? 'collage__img--tall' : '' }}" style="background-image:url('{{ $e->cover_image_url }}')"><span>{{ $e->category->icon }} {{ $e->type_label }}</span></div>
        @endforeach
        @if ($experiences->isNotEmpty())
          @php $top = $experiences->first(); @endphp
          <div class="float-card"><span class="avatar">{{ $top->host->user->initials() }}</span><div><div class="float-card__val">{{ money($top->host->plan->hostPayoutFor((float) $top->price * $top->max_guests)) }}</div><div class="float-card__lbl">{{ Str::before($top->host->display_name, ' ') }} recibe por una fecha completa</div></div></div>
        @endif
      </div>
    </div>
  </section>

  <div class="container">
    <form class="search" method="get" action="{{ route('home') }}#experiencias">
      <div class="search__field"><label for="search-cat">Qué querés vivir</label>
        <select id="search-cat" name="cat"><option value="">Todas</option>@foreach ($categories as $c)<option value="{{ $c->slug }}" @selected($activeCategory?->id === $c->id)>{{ $c->name }}</option>@endforeach</select></div>
      <div class="search__field"><label for="search-lugar">Dónde</label><input id="search-lugar" name="lugar" type="text" value="{{ request('lugar') }}" placeholder="La Rioja, Chilecito…"></div>
      <div class="search__field"><label for="search-fecha">Cuándo</label><input id="search-fecha" name="fecha" type="date" value="{{ request('fecha') }}"></div>
      <div class="search__submit"><button class="btn btn--primary" type="submit">Buscar</button></div>
    </form>
  </div>

  <section class="section section--tight">
    <div class="container stats">
      <div class="stat reveal"><div class="stat__val" data-count="{{ $stats['experiences'] }}">0</div><div class="stat__lbl">experiencias publicadas</div></div>
      <div class="stat reveal"><div class="stat__val" data-count="{{ $stats['reviews'] }}">0</div><div class="stat__lbl">opiniones verificadas</div></div>
      <div class="stat reveal"><div class="stat__val">{{ number_format($stats['rating'], 1, ',', '.') }}</div><div class="stat__lbl">puntaje promedio</div></div>
      <div class="stat reveal"><div class="stat__val" data-count="24" data-suffix=" h">0</div><div class="stat__lbl">para confirmar una reserva</div></div>
    </div>
  </section>

  <section class="section" id="experiencias" style="padding-top:20px">
    <div class="container">
      <div class="section-head">
        <div><p class="eyebrow">Experiencias cerca tuyo</p><h2 class="title">Elegí un plan. <span class="hl">Conocé</span> a quien lo crea.</h2></div>
        <p class="lead">Cada experiencia muestra qué incluye, quién la ofrece, el precio final y las opiniones de quienes ya fueron.</p>
      </div>
      <div class="chips">
        <a class="chip {{ $activeCategory ? '' : 'is-active' }}" href="{{ route('home', request()->except('cat')) }}#experiencias">✨ Todas</a>
        @foreach ($categories as $c)
          <a class="chip {{ $activeCategory?->id === $c->id ? 'is-active' : '' }}" href="{{ route('home', [...request()->except('cat'), 'cat' => $c->slug]) }}#experiencias">{{ $c->icon }} {{ $c->name }}</a>
        @endforeach
      </div>
      <div class="grid">
        @forelse ($experiences as $e)
          <x-experience-card :experience="$e" />
        @empty
          <div class="empty">Todavía no hay experiencias con ese filtro. ¿Querés ser quien publique la primera?</div>
        @endforelse
      </div>
    </div>
  </section>

  <section class="section section--alt" id="tipos">
    <div class="container">
      <p class="eyebrow">Qué podés ofrecer</p>
      <h2 class="title">No hace falta un local. Hace falta algo que sepas hacer bien.</h2>
      <div class="bento" style="margin-top:44px">
        <div class="bento__item bento__item--wide bento__item--tall reveal" style="background-image:url('https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&w=1200&q=70')"><div class="bento__icon">🍽️</div><h3>Comida en tu casa</h3><p>Una cena, un almuerzo de domingo, un asado. Vos ponés la mesa y la receta.</p></div>
        <div class="bento__item reveal" style="background-image:url('https://images.unsplash.com/photo-1556910103-1c02745aae4d?auto=format&fit=crop&w=700&q=70')"><div class="bento__icon">👩‍🍳</div><h3>Clases de cocina</h3><p>Cocinan, comen y se llevan la receta.</p></div>
        <div class="bento__item reveal" style="background-image:url('https://images.unsplash.com/photo-1501555088652-021faa106b9b?auto=format&fit=crop&w=700&q=70')"><div class="bento__icon">🚙</div><h3>Paseos y viajes</h3><p>Lugares que solo conoce quien vive ahí.</p></div>
        <div class="bento__item reveal" style="background-image:url('https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=700&q=70')"><div class="bento__icon">🧶</div><h3>Talleres</h3><p>Pan, cerámica, huerta, vino.</p></div>
        <div class="bento__item bento__item--solid reveal"><div class="bento__icon">✨</div><h3>Lo que se te ocurra</h3><p>Si vale la pena compartirlo en una tarde, tiene lugar en Tinku.</p></div>
      </div>
    </div>
  </section>

  <section class="section section--dark" id="como-ganas">
    <div class="container split" x-data="{ price: 40000, rate: {{ (float) $plans->first()->commission_rate }}, plan: '{{ $plans->first()->name }}', fmt(n) { return '$ ' + Math.round(n).toLocaleString('es-AR'); } }">
      <div>
        <p class="eyebrow">Ingresos para el anfitrión</p>
        <h2 class="title">Tinku gana solo cuando vos recibís una reserva.</h2>
        <p class="lead">Vos definís el precio y los cupos. El participante paga dentro de la plataforma. Tinku descuenta la comisión de tu plan y te liquida el saldo después de la experiencia.</p>
        <p class="lead">Mové el valor y cambiá de plan para simular una reserva individual.</p>
      </div>
      <div class="calc">
        <div class="calc__label">Precio por persona <span x-text="fmt(price)"></span></div>
        <input type="range" min="10000" max="120000" step="1000" x-model.number="price" :style="`--pct:${(price - 10000) / 1100}%`" aria-label="Precio por persona">
        <div class="calc__plans">
          @foreach ($plans as $p)
            <button type="button" :class="{ 'is-active': plan === '{{ $p->name }}' }" @click="plan = '{{ $p->name }}'; rate = {{ (float) $p->commission_rate }}">{{ $p->name }} {{ $p->commissionPercent() }}%</button>
          @endforeach
        </div>
        <div class="calc__row"><span>Precio cobrado</span><b x-text="fmt(price)"></b></div>
        <div class="calc__row"><span>Comisión Tinku (<span x-text="Math.round(rate * 100) + '%'"></span>) · plan <span x-text="plan"></span></span><b x-text="fmt(price * rate)"></b></div>
        <div class="calc__row calc__row--total"><span>Recibís vos*</span><span x-text="fmt(price - price * rate)"></span></div>
        <p class="calc__note">*Antes de impuestos, retenciones, costos de cobro y gastos propios.</p>
      </div>
    </div>
  </section>

  <section class="section" id="planes">
    <div class="container">
      <div class="section-head">
        <div><p class="eyebrow">Planes para anfitriones</p><h2 class="title">Empezá gratis. <span class="hl">Crecé</span> cuando quieras.</h2></div>
        <p class="lead">La comisión baja en los planes pagos. La suscripción empieza a convenir cuando recibís más reservas.</p>
      </div>
      <div class="plans">
        @foreach ($plans as $p)
          <article class="plan reveal {{ $p->is_featured ? 'plan--featured' : '' }}">
            @if ($p->is_featured)<span class="plan__badge">Recomendado</span>@endif
            <div class="plan__kind">{{ $p->tagline }}</div>
            <h3 class="plan__name">{{ $p->name }}</h3>
            <div class="plan__price">{{ money($p->monthly_price) }}<small>por mes</small></div>
            <div class="plan__fee">{{ $p->commissionPercent() }}% por reserva</div>
            <ul>@foreach ($p->features ?? [] as $f)<li>{{ $f }}</li>@endforeach</ul>
            <a class="btn {{ $p->is_featured ? 'btn--primary' : 'btn--outline' }}" href="{{ route('anfitrion.registro', ['plan' => $p->slug]) }}">Elegir {{ $p->name }}</a>
          </article>
        @endforeach
      </div>
    </div>
  </section>

  @if ($testimonials->isNotEmpty())
  <section class="section section--alt">
    <div class="container">
      <p class="eyebrow">Lo que dicen</p>
      <h2 class="title">Quienes ya fueron.</h2>
      <div class="quotes" style="margin-top:44px">
        @foreach ($testimonials as $t)
          <figure class="quote reveal">
            <p>“{{ $t->body }}”</p>
            <footer><span class="avatar">{{ $t->user->initials() }}</span><span><strong>{{ $t->user->name }}</strong><br>Sobre {{ $t->experience->title }}</span></footer>
          </figure>
        @endforeach
      </div>
    </div>
  </section>
  @endif

  <section class="section" id="camino">
    <div class="container">
      <p class="eyebrow">Camino del anfitrión</p>
      <h2 class="title">De tu idea a tu primera reserva.</h2>
      <div class="steps" style="margin-top:44px">
        <div class="step reveal"><div class="step__num">01</div><h3 class="step__title">Registrate</h3><p>Creá tu cuenta y confirmá email y teléfono.</p></div>
        <div class="step reveal"><div class="step__num">02</div><h3 class="step__title">Verificá</h3><p>Validá tu documento, tu identidad y el domicilio donde recibís.</p></div>
        <div class="step reveal"><div class="step__num">03</div><h3 class="step__title">Publicá</h3><p>Definí qué incluye, precio, fechas, cupos y condiciones.</p></div>
        <div class="step reveal"><div class="step__num">04</div><h3 class="step__title">Recibí</h3><p>Confirmá la reserva y construí reputación.</p></div>
      </div>
      <div class="cta reveal">
        <div><h2>Tu mesa, tu cocina o tu pueblo pueden ser una experiencia.</h2><p>Publicar es gratis. Cobrás cuando recibís.</p></div>
        <a class="btn btn--light" href="{{ route('anfitrion.registro') }}">Comenzar mi registro <span class="arrow">→</span></a>
      </div>
    </div>
  </section>
</x-layout>
