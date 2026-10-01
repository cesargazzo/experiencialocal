<nav class="admin-nav" aria-label="Tu cuenta">
  <a @class(['chip', 'is-active' => request()->routeIs('cuenta.perfil')]) href="{{ route('cuenta.perfil') }}" @if (request()->routeIs('cuenta.perfil')) aria-current="page" @endif><x-icon name="user-circle" :size="16" /> Perfil</a>
  <a @class(['chip', 'is-active' => request()->routeIs('cuenta.avisos')]) href="{{ route('cuenta.avisos') }}" @if (request()->routeIs('cuenta.avisos')) aria-current="page" @endif><x-icon name="bell" :size="16" /> Avisos</a>
  <a @class(['chip', 'is-active' => request()->routeIs('cuenta.reservas')]) href="{{ route('cuenta.reservas') }}" @if (request()->routeIs('cuenta.reservas')) aria-current="page" @endif><x-icon name="calendar-blank" :size="16" /> Reservas</a>
  <a @class(['chip', 'is-active' => request()->routeIs('cuenta.favoritas')]) href="{{ route('cuenta.favoritas') }}" @if (request()->routeIs('cuenta.favoritas')) aria-current="page" @endif><x-icon name="heart-fill" :size="16" /> Favoritas</a>
  <a @class(['chip', 'is-active' => request()->routeIs('cuenta.intereses')]) href="{{ route('cuenta.intereses') }}" @if (request()->routeIs('cuenta.intereses')) aria-current="page" @endif><x-icon name="heart" :size="16" /> Intereses</a>
  <a @class(['chip', 'is-active' => request()->routeIs('cuenta.seguridad')]) href="{{ route('cuenta.seguridad') }}" @if (request()->routeIs('cuenta.seguridad')) aria-current="page" @endif><x-icon name="shield-check" :size="16" /> Seguridad</a>
  <a @class(['chip', 'is-active' => request()->routeIs('verificacion')]) href="{{ route('verificacion') }}" @if (request()->routeIs('verificacion')) aria-current="page" @endif><x-icon name="seal-check" :size="16" /> Verificación</a>
  <a @class(['chip', 'is-active' => request()->routeIs('cuenta.invitaciones')]) href="{{ route('cuenta.invitaciones') }}" @if (request()->routeIs('cuenta.invitaciones')) aria-current="page" @endif><x-icon name="paper-plane-tilt" :size="16" /> Invitaciones</a>
</nav>
