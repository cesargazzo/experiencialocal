<nav class="admin-nav" aria-label="Administración">
  <a @class(['chip', 'is-active' => request()->routeIs('admin.usuarios*')]) href="{{ route('admin.usuarios') }}">Usuarios</a>
  <a @class(['chip', 'is-active' => request()->routeIs('admin.verificaciones')]) href="{{ route('admin.verificaciones') }}">Verificaciones</a>
  @php($experiencesToReview = \App\Models\Experience::where('status', \App\Enums\ExperienceStatus::InReview)->whereNull('approved_at')->count())
  <a @class(['chip', 'is-active' => request()->routeIs('admin.experiencias')]) href="{{ route('admin.experiencias') }}">Experiencias @if ($experiencesToReview)({{ $experiencesToReview }})@endif</a>
  @php($openReports = \App\Models\Message::whereNotNull('reported_at')->count())
  <a @class(['chip', 'is-active' => request()->routeIs('admin.denuncias')]) href="{{ route('admin.denuncias') }}">Denuncias @if ($openReports)({{ $openReports }})@endif</a>
  <a @class(['chip', 'is-active' => request()->routeIs('admin.terminos*')]) href="{{ route('admin.terminos') }}">Términos</a>
  <a @class(['chip', 'is-active' => request()->routeIs('admin.contrasenas')]) href="{{ route('admin.contrasenas') }}">Contraseñas</a>
  <a @class(['chip', 'is-active' => request()->routeIs('admin.seguridad')]) href="{{ route('admin.seguridad') }}">Seguridad</a>
  <a @class(['chip', 'is-active' => request()->routeIs('admin.registro')]) href="{{ route('admin.registro') }}">Errores</a>
  <a @class(['chip', 'is-active' => request()->routeIs('admin.auditoria')]) href="{{ route('admin.auditoria') }}">Auditoría</a>
  <a @class(['chip', 'is-active' => request()->routeIs('admin.configuracion')]) href="{{ route('admin.configuracion') }}">Configuración</a>
</nav>
