<nav class="admin-nav" aria-label="Administración">
  <a @class(['chip', 'is-active' => request()->routeIs('admin.verificaciones')]) href="{{ route('admin.verificaciones') }}">Verificaciones</a>
  <a @class(['chip', 'is-active' => request()->routeIs('admin.contrasenas')]) href="{{ route('admin.contrasenas') }}">Contraseñas</a>
</nav>
