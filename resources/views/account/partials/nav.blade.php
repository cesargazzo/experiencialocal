<nav class="admin-nav" aria-label="Tu cuenta">
  <a @class(['chip', 'is-active' => request()->routeIs('cuenta.perfil')]) href="{{ route('cuenta.perfil') }}" @if (request()->routeIs('cuenta.perfil')) aria-current="page" @endif><x-icon name="user-circle" :size="16" /> Perfil</a>
  <a @class(['chip', 'is-active' => request()->routeIs('cuenta.seguridad')]) href="{{ route('cuenta.seguridad') }}" @if (request()->routeIs('cuenta.seguridad')) aria-current="page" @endif><x-icon name="shield-check" :size="16" /> Seguridad</a>
  <a @class(['chip', 'is-active' => request()->routeIs('verificacion')]) href="{{ route('verificacion') }}" @if (request()->routeIs('verificacion')) aria-current="page" @endif><x-icon name="seal-check" :size="16" /> Verificación</a>
</nav>
