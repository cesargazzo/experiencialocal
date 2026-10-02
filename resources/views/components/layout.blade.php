@props([
    'title' => null,
    'description' => 'Comidas en casa, clases de cocina, paseos y talleres con personas locales. En Tinku reservás con la gente que vive ahí.',
    'image' => null,
    'imageAlt' => null,
    'type' => 'website',
    'canonical' => null,
    'noindex' => false,
    'jsonLd' => null,
])
@php
    $environmentLabel = match (app()->environment()) {
        'staging' => __('Entorno de prueba'),
        'local' => __('Entorno local'),
        default => __('Entorno :name', ['name' => app()->environment()]),
    };
    $fullTitle = $title ? $title.' · Tinku' : 'Tinku · Viví el lugar con su gente';
    $description = Str::limit(trim(preg_replace('/\s+/', ' ', $description)), 158);
    $canonical ??= url()->current();
    $image ??= asset('brand/tinku-og.png');
    $imageAlt ??= 'Tinku. Viví el lugar con su gente.';
    $indexable = config('tinku.indexable') && ! $noindex;
    $analyticsId = config('services.google_analytics.id');
    $cookieConsent = \App\Support\CookieConsent::fromRequest(request());
    $loadAnalytics = $analyticsId && $cookieConsent?->analytics;
    $askCookieConsent = \App\Support\CookieConsent::hasOptionalCookies() && $cookieConsent === null && ! request()->routeIs('cookies');
@endphp
<!doctype html>
<html lang="{{ config('tinku.locales.'.app()->getLocale().'.html', 'es-AR') }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $fullTitle }}</title>
  <meta name="description" content="{{ $description }}">
  <meta name="robots" content="{{ $indexable ? 'index, follow, max-image-preview:large' : 'noindex, nofollow' }}">
  <link rel="canonical" href="{{ $canonical }}">
  <meta name="theme-color" content="#FFF6EE">

  {{-- Open Graph: LinkedIn, Facebook, WhatsApp --}}
  <meta property="og:site_name" content="Tinku">
  <meta property="og:locale" content="{{ config('tinku.locales.'.app()->getLocale().'.og', 'es_AR') }}">
  <meta property="og:type" content="{{ $type }}">
  <meta property="og:title" content="{{ $title ?? 'Tinku · Viví el lugar con su gente' }}">
  <meta property="og:description" content="{{ $description }}">
  <meta property="og:url" content="{{ $canonical }}">
  <meta property="og:image" content="{{ $image }}">
  <meta property="og:image:secure_url" content="{{ $image }}">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:image:alt" content="{{ $imageAlt }}">

  {{-- X / Twitter --}}
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="{{ $title ?? 'Tinku · Viví el lugar con su gente' }}">
  <meta name="twitter:description" content="{{ $description }}">
  <meta name="twitter:image" content="{{ $image }}">
  <meta name="twitter:image:alt" content="{{ $imageAlt }}">

  <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32 48x48">
  <link rel="icon" type="image/svg+xml" href="{{ asset('brand/tinku-avatar.svg') }}">
  <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('brand/tinku-icon-192.png') }}">
  <link rel="apple-touch-icon" href="{{ asset('brand/tinku-icon-180.png') }}">
  <link rel="manifest" href="{{ asset('site.webmanifest') }}">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700&family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">

  <script type="application/ld+json">{!! json_encode($jsonLd ?? [
      '@context' => 'https://schema.org',
      '@graph' => [
          ['@type' => 'Organization', '@id' => url('/').'#organizacion', 'name' => 'Tinku', 'url' => url('/'), 'logo' => asset('brand/tinku-icon-512.png'), 'slogan' => 'Viví el lugar con su gente'],
          ['@type' => 'WebSite', '@id' => url('/').'#sitio', 'name' => 'Tinku', 'url' => url('/'), 'inLanguage' => 'es-AR', 'publisher' => ['@id' => url('/').'#organizacion']],
      ],
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>

  {{-- Google Analytics solo con el permiso de la persona (ver /cookies). Sus cookies quedan en este dominio para poder borrarlas si lo retira. --}}
  @if ($loadAnalytics)
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($analyticsId) }}"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', @json($analyticsId), { cookie_domain: @json(request()->getHost()), allow_google_signals: false, allow_ad_personalization_signals: false });
    </script>
  @endif
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @livewireStyles
</head>
<body>
  @unless (app()->isProduction())
    <div class="proto-banner"><strong>{{ $environmentLabel }}</strong> {{ __('Las experiencias, precios y registros son demostrativos.') }}</div>
  @endunless

  <header class="header">
    <div class="container header__inner">
      <a class="brand" href="{{ route('home') }}">
        <img src="{{ asset('brand/tinku-horizontal.svg') }}" alt="{{ __('Tinku, inicio') }}" width="114" height="40">
      </a>
      <button class="nav-toggle" type="button" aria-label="{{ __('Abrir menú') }}" aria-expanded="false"><x-icon name="list" :size="24" /></button>
      <nav @class(['nav', 'nav--auth' => auth()->check()]) aria-label="{{ __('Principal') }}">
        <a href="{{ route('home') }}#experiencias">{{ __('Experiencias') }}</a>
        <a href="{{ route('home') }}#como-ganas">{{ __('Cómo ganás') }}</a>
        <a href="{{ route('home') }}#planes">{{ __('Planes') }}</a>
        @auth
          @if (auth()->user()->isHost())
            @php($pendingHostBookings = auth()->user()->pendingHostBookingsCount())
            <a class="btn btn--secondary btn--sm" href="{{ route('anfitrion.panel') }}{{ $pendingHostBookings ? '#reservas' : '' }}">
              {{ __('Mi espacio de anfitrión') }}
              @if ($pendingHostBookings)<span class="btn__count" aria-label="{{ plural_es($pendingHostBookings, __('reserva por confirmar'), __('reservas por confirmar')) }}">{{ $pendingHostBookings }}</span>@endif
            </a>
          @else
            <a class="btn btn--secondary btn--sm" href="{{ route('anfitrion.registro') }}">{{ __('Quiero ser anfitrión') }}</a>
          @endif
          @php($unreadConversations = \App\Models\Conversation::unreadCountFor(auth()->user()))
          <a class="bell" href="{{ route('mensajes') }}" aria-label="{{ $unreadConversations ? __('Mensajes: :count sin leer', ['count' => $unreadConversations]) : __('Mensajes') }}">
            <x-icon name="envelope-simple" :size="22" />
            @if ($unreadConversations)<span class="bell__count">{{ $unreadConversations > 9 ? '9+' : $unreadConversations }}</span>@endif
          </a>
          @php($unreadNotifications = auth()->user()->unreadNotifications()->count())
          <a class="bell" href="{{ route('cuenta.avisos') }}" aria-label="{{ $unreadNotifications ? __('Avisos: :count sin leer', ['count' => $unreadNotifications]) : __('Avisos') }}">
            <x-icon name="bell" :size="22" />
            @if ($unreadNotifications)<span class="bell__count">{{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}</span>@endif
          </a>
          <x-profile-menu />
        @else
          <a class="btn btn--secondary btn--sm" href="{{ route('anfitrion.registro') }}">{{ __('Quiero ser anfitrión') }}</a>
          <a href="{{ route('login') }}">{{ __('Ingresar') }}</a>
        @endauth
      </nav>
    </div>
  </header>

  @auth
    @if (auth()->user()->isBirthdayToday())
      @php($birthdayKey = 'tinku-cumple-'.auth()->id().'-'.now(config('tinku.timezone'))->year)
      <div class="birthday-banner" role="status" x-data="{ open: true }" x-init="try { open = localStorage.getItem(@js($birthdayKey)) !== '1' } catch (e) {}" x-show="open" x-cloak>
        <div class="container birthday-banner__inner">
          <x-icon name="sparkle" :size="22" />
          <p><strong>{{ __('¡Feliz cumpleaños, :name!', ['name' => Str::before(auth()->user()->name.' ', ' ')]) }}</strong> {{ __('Que tengas un gran día. Gracias por ser parte de Tinku.') }}</p>
          <button type="button" class="birthday-banner__close" aria-label="{{ __('Cerrar el saludo') }}" x-on:click="open = false; try { localStorage.setItem(@js($birthdayKey), '1') } catch (e) {}"><x-icon name="x" :size="18" /></button>
        </div>
      </div>
    @endif
  @endauth
  {{ $slot }}

  @if ($askCookieConsent)
    <section class="cookie-banner" role="region" aria-label="{{ __('Cookies') }}">
      <p><strong>{{ __('Cookies:') }}</strong> {{ __('usamos cookies propias, necesarias para que Tinku funcione (tu sesión, la seguridad y tu idioma). Si nos dejás, también usamos las de Google Analytics para entender cómo se usa el sitio y mejorarlo. No usamos cookies de publicidad ni armamos perfiles para mostrarte anuncios.') }}
        <a href="{{ route('cookies') }}">{{ __('Más información sobre las cookies') }}</a> · <a href="{{ route('cookies') }}#terceros">{{ __('Ver con quién trabajamos') }}</a></p>
      <div class="cookie-banner__actions">
        <form method="post" action="{{ route('cookies.guardar') }}">@csrf<input type="hidden" name="analytics" value="1"><button class="btn btn--secondary btn--sm" type="submit">{{ __('Aceptar todas') }}</button></form>
        <form method="post" action="{{ route('cookies.guardar') }}">@csrf<input type="hidden" name="analytics" value="0"><button class="btn btn--secondary btn--sm" type="submit">{{ __('Solo las necesarias') }}</button></form>
        <a class="btn btn--ghost btn--sm" href="{{ route('cookies') }}#preferencias">{{ __('Configurar') }}</a>
      </div>
    </section>
  @endif

  <footer class="footer">
    <div class="container">
      <div class="footer__grid">
        <div>
          <a href="{{ route('home') }}"><img class="footer__logo" src="{{ asset('brand/tinku-principal.svg') }}" alt="{{ __('Tinku. Viví el lugar con su gente.') }}" width="132" height="146"></a>
          <p style="margin-top:20px">{{ __('Tinku significa encuentro en quechua y aymara. Conocé un lugar a través de la gente que lo habita.') }}</p>
        </div>
        <div><h4>{{ __('Explorar') }}</h4><ul>
          @foreach (\App\Models\Category::orderBy('sort_order')->get() as $c)
            <li><a href="{{ route('home', ['cat' => $c->slug]) }}#experiencias">{{ $c->label() }}</a></li>
          @endforeach
        </ul></div>
        <div><h4>{{ __('Anfitriones') }}</h4><ul><li><a href="{{ route('anfitrion.registro') }}">{{ __('Publicar una experiencia') }}</a></li><li><a href="{{ route('home') }}#planes">{{ __('Planes') }}</a></li><li><a href="{{ route('home') }}#como-ganas">{{ __('Cómo ganás') }}</a></li><li><a href="{{ route('terminos') }}">{{ __('Términos y condiciones') }}</a></li></ul></div>
        <div><h4>Tinku</h4><ul><li><a href="{{ route('verificacion') }}">{{ __('Verificación de identidad') }}</a></li><li><a href="{{ route('seguridad') }}">{{ __('Seguridad') }}</a></li><li><a href="{{ route('ayuda') }}">{{ __('Ayuda') }}</a></li><li><a href="{{ route('mediakit') }}">{{ __('Anunciá en Tinku') }}</a></li><li><a href="{{ route('cookies') }}">{{ __('Política de cookies') }}</a></li></ul></div>
      </div>
      <div class="footer__bottom">
        <span>© {{ date('Y') }} Tinku</span>
        <form method="post" action="{{ route('idioma') }}" class="locale-switch">
          @csrf
          <label for="locale-switch" class="sr-only">{{ __('Idioma') }}</label>
          <x-icon name="globe" :size="16" />
          <select id="locale-switch" name="locale" onchange="this.form.submit()">
            @foreach (config('tinku.locales') as $code => $locale)<option value="{{ $code }}" @selected(app()->getLocale() === $code) lang="{{ $locale['html'] }}">{{ $locale['name'] }}</option>@endforeach
          </select>
          <noscript><button type="submit">OK</button></noscript>
        </form>
      </div>
    </div>
  </footer>

  @if (session('status'))
    <div class="toast is-visible" role="status" x-data x-init="setTimeout(() => $el.classList.remove('is-visible'), 4000)">{{ session('status') }}</div>
  @endif
  @livewireScripts
</body>
</html>
