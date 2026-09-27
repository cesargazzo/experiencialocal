@props(['title' => null, 'description' => 'Tinku: experiencias con personas locales. Comidas en casa, clases de cocina, paseos y talleres.'])
<!doctype html>
<html lang="es-AR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="{{ $description }}">
  <title>{{ $title ? $title.' · Tinku' : 'Tinku · Experiencias con personas locales' }}</title>
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0' y1='0' x2='1' y2='1'%3E%3Cstop offset='0' stop-color='%23ff5a3c'/%3E%3Cstop offset='1' stop-color='%23ff8a3c'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='64' height='64' rx='16' fill='url(%23g)'/%3E%3Cpath d='M14 40c6-14 14-14 18 0M32 40c4-14 12-14 18 0' fill='none' stroke='%23fff' stroke-width='5' stroke-linecap='round'/%3E%3C/svg%3E">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @livewireStyles
</head>
<body>
  @unless (app()->isProduction())
    <div class="proto-banner"><strong>ENTORNO {{ strtoupper(app()->environment()) }}</strong> Las experiencias, precios y registros son demostrativos.</div>
  @endunless

  <header class="header">
    <div class="container header__inner">
      <a class="brand" href="{{ route('home') }}" aria-label="Tinku, inicio">
        <x-brand-mark />
        <span class="brand__name">Tinku</span>
      </a>
      <button class="nav-toggle" aria-label="Abrir menú"><span></span><span></span><span></span></button>
      <nav class="nav">
        <a href="{{ route('home') }}#experiencias">Experiencias</a>
        <a href="{{ route('home') }}#como-ganas">Cómo ganás</a>
        <a href="{{ route('home') }}#planes">Planes</a>
        @auth
          <a href="{{ route('verificacion') }}" title="Nivel de verificación">{{ auth()->user()->name }} <x-verification-badge :level="auth()->user()->verification_level" /></a>
          @if (auth()->user()->isAdmin())
            <a href="{{ route('admin.verificaciones') }}">Admin</a>
          @endif
          <form method="post" action="{{ route('logout') }}" style="display:inline">@csrf<button class="btn btn--ghost btn--sm" type="submit">Salir</button></form>
          @if (auth()->user()->isHost())
            <a class="btn btn--dark btn--sm" href="{{ route('anfitrion.registro') }}">Mi perfil de anfitrión</a>
          @else
            <a class="btn btn--dark btn--sm" href="{{ route('anfitrion.registro') }}">Quiero ser anfitrión</a>
          @endif
        @else
          <a href="{{ route('login') }}">Ingresar</a>
          <a class="btn btn--dark btn--sm" href="{{ route('anfitrion.registro') }}">Quiero ser anfitrión</a>
        @endauth
      </nav>
    </div>
  </header>

  {{ $slot }}

  <footer class="footer">
    <div class="container">
      <div class="footer__grid">
        <div>
          <a class="brand" href="{{ route('home') }}"><x-brand-mark /><span class="brand__name">Tinku</span></a>
          <p style="margin-top:14px">Tinku significa encuentro en quechua. Experiencias con personas locales: comidas, clases, paseos y talleres.</p>
        </div>
        <div><h4>Explorar</h4><ul>
          @foreach (\App\Models\Category::orderBy('sort_order')->get() as $c)
            <li><a href="{{ route('home', ['cat' => $c->slug]) }}#experiencias">{{ $c->name }}</a></li>
          @endforeach
        </ul></div>
        <div><h4>Anfitriones</h4><ul><li><a href="{{ route('anfitrion.registro') }}">Publicar experiencia</a></li><li><a href="{{ route('home') }}#planes">Planes</a></li><li><a href="{{ route('home') }}#como-ganas">Cómo ganás</a></li></ul></div>
        <div><h4>Tinku</h4><ul><li><a href="{{ route('verificacion') }}">Verificación de identidad</a></li><li><a href="#">Seguridad</a></li><li><a href="#">Ayuda</a></li></ul></div>
      </div>
      <div class="footer__bottom">
        <span>© {{ date('Y') }} Tinku</span>
        <span>Encontrate con lo local.</span>
      </div>
    </div>
  </footer>

  @if (session('status'))
    <div class="toast is-visible" x-data x-init="setTimeout(() => $el.classList.remove('is-visible'), 4000)">{{ session('status') }}</div>
  @endif
  @livewireScripts
</body>
</html>
