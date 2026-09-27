@props(['title' => null, 'description' => 'Tinku: viví el lugar con su gente. Comidas en casa, clases de cocina, paseos y talleres con personas locales.'])
@php
    $environmentLabel = match (app()->environment()) {
        'staging' => 'Entorno de prueba',
        'local' => 'Entorno local',
        default => 'Entorno '.app()->environment(),
    };
@endphp
<!doctype html>
<html lang="es-AR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="{{ $description }}">
  <meta name="theme-color" content="#FFF6EE">
  <title>{{ $title ? $title.' · Tinku' : 'Tinku · Viví el lugar con su gente' }}</title>
  <link rel="icon" type="image/svg+xml" href="{{ asset('brand/tinku-avatar.svg') }}">
  <link rel="apple-touch-icon" href="{{ asset('brand/tinku-avatar.svg') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700&family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">
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
          <a href="{{ route('verificacion') }}" title="Tu verificación de identidad">{{ Str::before(auth()->user()->name, ' ') }} <x-verification-badge :level="auth()->user()->verification_level" /></a>
          @if (auth()->user()->isAdmin())
            <a href="{{ route('admin.verificaciones') }}">Administración</a>
          @endif
          <form method="post" action="{{ route('logout') }}" style="display:inline">@csrf<button class="btn btn--ghost btn--sm" type="submit">Salir</button></form>
          <a class="btn btn--secondary btn--sm" href="{{ route('anfitrion.registro') }}">{{ auth()->user()->isHost() ? 'Mi perfil de anfitrión' : 'Quiero ser anfitrión' }}</a>
        @else
          <a href="{{ route('login') }}">Ingresar</a>
          <a class="btn btn--secondary btn--sm" href="{{ route('anfitrion.registro') }}">Quiero ser anfitrión</a>
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
        <div><h4>Anfitriones</h4><ul><li><a href="{{ route('anfitrion.registro') }}">Publicar una experiencia</a></li><li><a href="{{ route('home') }}#planes">Planes</a></li><li><a href="{{ route('home') }}#como-ganas">Cómo ganás</a></li></ul></div>
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
