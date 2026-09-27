@php $user = auth()->user()->loadMissing('avatar'); @endphp
<div class="profile-menu" x-data="{ open: false }" x-on:keydown.escape.window="open = false" x-on:click.outside="open = false">
  <button
    type="button"
    class="profile-menu__button"
    x-on:click="open = ! open"
    x-bind:aria-expanded="open.toString()"
    aria-expanded="false"
    aria-controls="profile-menu-panel"
  >
    <x-avatar :user="$user" :size="36" />
    <span class="profile-menu__name">{{ Str::before($user->name, ' ') }}</span>
    <x-icon name="caret-down" :size="16" class="profile-menu__caret" />
  </button>

  <div id="profile-menu-panel" class="profile-menu__panel" x-show="open" x-transition.origin.top.right x-cloak>
    <div class="profile-menu__header">
      <x-avatar :user="$user" :size="44" />
      <div>
        <strong>{{ $user->name }}</strong>
        <span>{{ $user->email }}</span>
        <x-verification-badge :level="$user->verification_level" full />
      </div>
    </div>
    <a href="{{ route('cuenta.perfil') }}"><x-icon name="user-circle" :size="18" /> Perfil y foto</a>
    <a href="{{ route('cuenta.reservas') }}"><x-icon name="calendar-blank" :size="18" /> Mis reservas</a>
    <a href="{{ route('cuenta.intereses') }}"><x-icon name="heart" :size="18" /> Intereses y avisos</a>
    <a href="{{ route('cuenta.seguridad') }}"><x-icon name="shield-check" :size="18" /> Seguridad y contraseña</a>
    <a href="{{ route('verificacion') }}"><x-icon name="seal-check" :size="18" /> Verificación de identidad</a>
    <a href="{{ route('cuenta.invitaciones') }}"><x-icon name="paper-plane-tilt" :size="18" /> Invitá a alguien</a>
    @if ($user->isAdmin())
      <a href="{{ route('admin.usuarios') }}"><x-icon name="gear" :size="18" /> Administración</a>
    @endif
    <form method="post" action="{{ route('logout') }}">
      @csrf
      <button type="submit"><x-icon name="sign-out" :size="18" /> Salir</button>
    </form>
  </div>
</div>
