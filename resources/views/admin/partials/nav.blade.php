<nav class="admin-nav" aria-label="Administración">
  <a @class(['chip', 'is-active' => request()->routeIs('admin.usuarios*')]) href="{{ route('admin.usuarios') }}">Usuarios</a>
  <a @class(['chip', 'is-active' => request()->routeIs('admin.verificaciones')]) href="{{ route('admin.verificaciones') }}">Verificaciones</a>
  <a @class(['chip', 'is-active' => request()->routeIs('admin.contrasenas')]) href="{{ route('admin.contrasenas') }}">Contraseñas</a>
  <a @class(['chip', 'is-active' => request()->routeIs('admin.seguridad')]) href="{{ route('admin.seguridad') }}">Seguridad</a>
  <a @class(['chip', 'is-active' => request()->routeIs('admin.configuracion')]) href="{{ route('admin.configuracion') }}">Configuración</a>
</nav>
