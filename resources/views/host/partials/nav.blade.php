<nav class="admin-nav" aria-label="Tu espacio de anfitrión">
  @foreach ([['anfitrion.panel', 'Panel', 'sparkle'], ['anfitrion.calendario', 'Calendario', 'calendar-blank'], ['anfitrion.ganancias', 'Ganancias', 'wallet'], ['anfitrion.estadisticas', 'Estadísticas', 'star']] as [$route, $label, $icon])
    {{-- En componentes Livewire la ruta cambia al actualizar: la vista avisa cuál es la activa. --}}
    @php($isActive = isset($activeHostNav) ? $activeHostNav === $route : request()->routeIs($route))
    <a @class(['chip', 'is-active' => $isActive]) href="{{ route($route) }}" @if ($isActive) aria-current="page" @endif><x-icon :name="$icon" :size="16" /> {{ $label }}</a>
  @endforeach
</nav>
