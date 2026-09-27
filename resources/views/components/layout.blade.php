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
        'staging' => 'Entorno de prueba',
        'local' => 'Entorno local',
        default => 'Entorno '.app()->environment(),
    };
    $fullTitle = $title ? $title.' · Tinku' : 'Tinku · Viví el lugar con su gente';
    $description = Str::limit(trim(preg_replace('/\s+/', ' ', $description)), 158);
    $canonical ??= url()->current();
    $image ??= asset('brand/tinku-og.png');
    $imageAlt ??= 'Tinku. Viví el lugar con su gente.';
    $indexable = config('tinku.indexable') && ! $noindex;
    $analyticsId = config('services.google_analytics.id');
@endphp
<!doctype html>
<html lang="es-AR">
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
  <meta property="og:locale" content="es_AR">
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

  @if ($analyticsId)
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($analyticsId) }}"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', @json($analyticsId));
    </script>
  @endif
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @livewireStyles
</head>
<body>
  @unless (app()->isProduction())
    <div class="proto-banner"><strong>{{ $environmentLabel }}</strong> Las experiencias, precios y registros son demostrativos.</div>
  @endunless

  <header class="header">
    <div class="container header__inner">
      <a class="brand" href="{{ route('home') }}">
        <img src="{{ asset('brand/tinku-horizontal.svg') }}" alt="Tinku, inicio" width="114" height="40">
      </a>
      <button class="nav-toggle" type="button" aria-label="Abrir menú" aria-expanded="false"><x-icon name="list" :size="24" /></button>
      <nav class="nav" aria-label="Principal">
        <a href="{{ route('home') }}#experiencias">Experiencias</a>
        <a href="{{ route('home') }}#como-ganas">Cómo ganás</a>
        <a href="{{ route('home') }}#planes">Planes</a>
        @auth
          <a class="btn btn--secondary btn--sm" href="{{ route(auth()->user()->isHost() ? 'anfitrion.panel' : 'anfitrion.registro') }}">{{ auth()->user()->isHost() ? 'Mi espacio de anfitrión' : 'Quiero ser anfitrión' }}</a>
          @php($unreadNotifications = auth()->user()->unreadNotifications()->count())
          <a class="bell" href="{{ route('cuenta.avisos') }}" aria-label="Avisos{{ $unreadNotifications ? ': '.$unreadNotifications.' sin leer' : '' }}">
            <x-icon name="bell" :size="22" />
            @if ($unreadNotifications)<span class="bell__count">{{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}</span>@endif
          </a>
          <x-profile-menu />
        @else
          <a class="btn btn--secondary btn--sm" href="{{ route('anfitrion.registro') }}">Quiero ser anfitrión</a>
          <a href="{{ route('login') }}">Ingresar</a>
        @endauth
      </nav>
    </div>
  </header>

  {{ $slot }}

  <footer class="footer">
    <div class="container">
      <div class="footer__grid">
        <div>
          <a href="{{ route('home') }}"><img class="footer__logo" src="{{ asset('brand/tinku-principal.svg') }}" alt="Tinku. Viví el lugar con su gente." width="132" height="146"></a>
          <p style="margin-top:20px">Tinku significa encuentro en quechua y aymara. Conocé un lugar a través de la gente que lo habita.</p>
        </div>
        <div><h4>Explorar</h4><ul>
          @foreach (\App\Models\Category::orderBy('sort_order')->get() as $c)
            <li><a href="{{ route('home', ['cat' => $c->slug]) }}#experiencias">{{ $c->name }}</a></li>
          @endforeach
        </ul></div>
        <div><h4>Anfitriones</h4><ul><li><a href="{{ route('anfitrion.registro') }}">Publicar una experiencia</a></li><li><a href="{{ route('home') }}#planes">Planes</a></li><li><a href="{{ route('home') }}#como-ganas">Cómo ganás</a></li><li><a href="{{ route('terminos') }}">Términos y condiciones</a></li></ul></div>
        <div><h4>Tinku</h4><ul><li><a href="{{ route('verificacion') }}">Verificación de identidad</a></li><li><a href="#">Seguridad</a></li><li><a href="#">Ayuda</a></li></ul></div>
      </div>
      <div class="footer__bottom">
        <span>© {{ date('Y') }} Tinku</span>
      </div>
    </div>
  </footer>

  @if (session('status'))
    <div class="toast is-visible" role="status" x-data x-init="setTimeout(() => $el.classList.remove('is-visible'), 4000)">{{ session('status') }}</div>
  @endif
  @livewireScripts
</body>
</html>
