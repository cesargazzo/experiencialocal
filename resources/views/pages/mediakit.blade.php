@php
  $asOf = \Illuminate\Support\Carbon::parse($as_of)->timezone(config('tinku.timezone'));
  $tiles = [
      ['value' => number_format($numbers['experiences'], 0, ',', '.'), 'label' => __('Experiencias publicadas')],
      ['value' => number_format($numbers['hosts'], 0, ',', '.'), 'label' => __('Anfitriones con identidad validada')],
      ['value' => number_format($numbers['provinces'], 0, ',', '.'), 'label' => __('Provincias con experiencias')],
      ['value' => number_format($numbers['people'], 0, ',', '.'), 'label' => __('Personas registradas')],
      ['value' => number_format($numbers['visits'], 0, ',', '.'), 'label' => __('Visitas a experiencias en 30 días')],
      ['value' => number_format($numbers['favorites'], 0, ',', '.'), 'label' => __('Experiencias guardadas en favoritas')],
      ['value' => number_format($numbers['bookings'], 0, ',', '.'), 'label' => __('Reservas confirmadas')],
      ['value' => $numbers['reviews'] ? decimal($numbers['rating']).' / 5' : '—', 'label' => __('Puntaje promedio en :count opiniones', ['count' => $numbers['reviews']])],
  ];
  $audiences = [['title' => __('Edades'), 'rows' => $ages], ['title' => __('De dónde son'), 'rows' => $origins], ['title' => __('Qué les interesa'), 'rows' => $interests]];
@endphp
<x-layout :title="__('Mediakit para anunciantes')" :description="__('Quiénes usan Tinku y cómo tu marca puede acompañar experiencias con gente local.')">
  <main class="container mediakit">
    <section class="mediakit__hero">
      <p class="eyebrow">{{ __('Mediakit') }}</p>
      <h1 class="title">{!! __('Llegá a quienes viajan <span class="hl">para conocer de verdad</span>.') !!}</h1>
      <p class="lead">{{ __('Tinku conecta a personas que viajan con anfitriones locales que las reciben en su casa, su cocina o su lugar. Cada cuenta tiene la identidad validada y cada experiencia la revisa una persona del equipo.') }}</p>
      <div class="mediakit__actions">
        <a class="btn btn--primary" href="#contacto">{{ __('Quiero anunciar') }}</a>
      </div>
    </section>

    <section class="mediakit__section">
      <h2>{{ __('Tinku en números') }}</h2>
      <p class="hint">{{ __('Datos reales al :date. Se actualizan solos.', ['date' => $asOf->isoFormat('LL')]) }}</p>
      <div class="kpis kpis--static mediakit__tiles">
        @foreach ($tiles as $tile)
          <div class="kpi"><span class="kpi__val">{{ $tile['value'] }}</span><span class="kpi__lbl">{{ $tile['label'] }}</span></div>
        @endforeach
      </div>
    </section>

    <section class="mediakit__section">
      <h2>{{ __('Quiénes nos eligen') }}</h2>
      <p class="hint">{{ __('Mostramos solo porcentajes de grupos grandes: nunca datos de una persona ni nada que permita reconocerla.') }}</p>
      <div class="mediakit__audience">
        @foreach ($audiences as $audience)
          <figure class="share">
            <figcaption>{{ $audience['title'] }}</figcaption>
            @if ($audience['rows'])
              <ul class="share__list">
                @foreach ($audience['rows'] as $row)
                  <li title="{{ $row['label'] === 'Otros' ? __('Otros') : __($row['label']) }}: {{ $row['share'] }}%">
                    <span class="share__label">{{ $row['label'] === 'Otros' ? __('Otros') : __($row['label']) }}</span>
                    <span class="share__track" aria-hidden="true"><span class="share__bar" style="width: {{ max(2, $row['share']) }}%"></span></span>
                    <span class="share__value">{{ $row['share'] }}%</span>
                  </li>
                @endforeach
              </ul>
            @else
              <p class="hint">{{ __('Lo vamos a mostrar cuando la comunidad sea más grande, para que nadie quede expuesto.') }}</p>
            @endif
          </figure>
        @endforeach
      </div>
      <ul class="mediakit__traits">
        <li><strong>{{ __('Viajan para vivir el lugar') }}</strong> {{ __('Buscan comida casera, oficios, paisajes y charlas con quien vive ahí, no solo una foto.') }}</li>
        <li><strong>{{ __('Planifican y reservan') }}</strong> {{ __('Eligen fecha, cuántos van y qué comen. Reservan con anticipación y dejan su opinión después.') }}</li>
        <li><strong>{{ __('Confían en lo verificado') }}</strong> {{ __('Todas las cuentas validan su identidad: tu marca aparece en un entorno cuidado.') }}</li>
      </ul>
    </section>

    <section class="mediakit__section">
      <h2>{{ __('Formas de anunciar') }}</h2>
      <div class="mediakit__formats">
        <article class="format">
          <h3>{{ __('Colección presentada por tu marca') }}</h3>
          <p>{{ __('Una selección de experiencias en el inicio, con tu nombre: «Sabores del norte, presentado por…».') }}</p>
          <p class="format__meta"><strong>{{ __('Ideal para') }}</strong> {{ __('Bodegas, alimentos, bebidas, turismo regional.') }}</p>
        </article>
        <article class="format">
          <h3>{{ __('Espacio patrocinado en el inicio y la búsqueda') }}</h3>
          <p>{{ __('Una tarjeta destacada entre los resultados, siempre marcada como «Patrocinado».') }}</p>
          <p class="format__meta"><strong>{{ __('Ideal para') }}</strong> {{ __('Alojamientos, transporte, equipamiento de viaje.') }}</p>
        </article>
        <article class="format">
          <h3>{{ __('Experiencias creadas con tu marca') }}</h3>
          <p>{{ __('Diseñamos con anfitriones una experiencia que muestre tu producto donde se hace o se usa: una cata, una clase, una salida.') }}</p>
          <p class="format__meta"><strong>{{ __('Ideal para') }}</strong> {{ __('Productores, marcas de cocina, outdoor.') }}</p>
        </article>
        <article class="format">
          <h3>{{ __('Alianza de turismo') }}</h3>
          <p>{{ __('Rutas, temporadas y circuitos de una provincia o municipio, con sus anfitriones y una página propia.') }}</p>
          <p class="format__meta"><strong>{{ __('Ideal para') }}</strong> {{ __('Organismos de turismo, cámaras y hoteles.') }}</p>
        </article>
        <article class="format">
          <h3>{{ __('Beneficios para la comunidad') }}</h3>
          <p>{{ __('Un descuento o regalo para quienes reservan, que llega con la confirmación de la reserva.') }}</p>
          <p class="format__meta"><strong>{{ __('Ideal para') }}</strong> {{ __('Comercios locales, servicios para viajeros.') }}</p>
        </article>
      </div>
    </section>

    <section class="mediakit__section">
      <h2>{{ __('Cómo trabajamos') }}</h2>
      <ul class="mediakit__principles">
        <li><x-icon name="shield-check" :size="20" /> <span><strong>{{ __('Sin datos personales.') }}</strong> {{ __('Las marcas reciben resultados en totales, nunca listas de personas ni contactos.') }}</span></li>
        <li><x-icon name="lock-key" :size="20" /> <span><strong>{{ __('Sin seguimiento entre sitios.') }}</strong> {{ __('No usamos cookies de terceros ni retargeting.') }}</span></li>
        <li><x-icon name="seal-check" :size="20" /> <span><strong>{{ __('Siempre identificado.') }}</strong> {{ __('Todo lo pago se marca como «Patrocinado».') }}</span></li>
        <li><x-icon name="heart" :size="20" /> <span><strong>{{ __('Que sume al viaje.') }}</strong> {{ __('Revisamos cada propuesta para que sea útil para quien viaja y justa con los anfitriones.') }}</span></li>
      </ul>
    </section>

    <section class="mediakit__section wizard__panel" id="contacto">
      <h2>{{ __('Contanos qué querés hacer') }}</h2>
      <p>{{ __('Te respondemos con una propuesta y la tarifa según el alcance.') }}</p>
      <form method="post" action="{{ route('mediakit.store') }}">
        @csrf
        <div class="sr-only" aria-hidden="true"><label for="website">Website</label><input id="website" name="website" tabindex="-1" autocomplete="off"></div>
        <div class="grid-2">
          <div class="field"><label for="mk-name">{{ __('Tu nombre') }}</label><input id="mk-name" name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name">@error('name')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
          <div class="field"><label for="mk-company">{{ __('Empresa u organismo') }}</label><input id="mk-company" name="company" value="{{ old('company') }}" required maxlength="160" autocomplete="organization">@error('company')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
          <div class="field"><label for="mk-email">{{ __('Email') }}</label><input id="mk-email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email">@error('email')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
          <div class="field"><label for="mk-phone">{{ __('Teléfono (opcional)') }}</label><input id="mk-phone" name="phone" type="tel" value="{{ old('phone') }}" maxlength="40" autocomplete="tel"></div>
        </div>
        <fieldset class="field-group">
          <legend>{{ __('Qué te interesa') }}</legend>
          <div class="choice-grid">
            @foreach ($formats as $key => $label)
              <label class="choice"><input type="checkbox" name="formats[]" value="{{ $key }}" @checked(in_array($key, old('formats', []), true))> <span>{{ __($label) }}</span></label>
            @endforeach
          </div>
        </fieldset>
        <div class="field"><label for="mk-budget">{{ __('Presupuesto aproximado') }}</label>
          <select id="mk-budget" name="budget"><option value="">{{ __('Elegí una opción') }}</option>@foreach ($budgets as $key => $label)<option value="{{ $key }}" @selected(old('budget') === $key)>{{ __($label) }}</option>@endforeach</select>
        </div>
        <div class="field"><label for="mk-message">{{ __('Qué querés comunicar') }}</label><textarea id="mk-message" name="message" rows="5" required minlength="20" maxlength="2000">{{ old('message') }}</textarea>@error('message')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
        <div class="wizard__actions"><span></span><button class="btn btn--primary" type="submit">{{ __('Enviá la consulta') }}</button></div>
      </form>
    </section>
  </main>
</x-layout>
