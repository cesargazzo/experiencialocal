<x-layout title="Equipo" :noindex="true">
  <main class="container wizard" style="max-width:920px">
    <p class="eyebrow">Administración</p>
    <h1 class="title">El <span class="hl">equipo</span>.</h1>
    @include('admin.partials.nav')

    <section class="wizard__panel">
      <h2>Quiénes son</h2>
      <p>Para sumar a alguien o cambiarle el rol, buscalo en <a class="link" href="{{ route('admin.usuarios') }}">Usuarios</a> y elegí el rol al final de su ficha. Hace falta la identidad validada y, para entrar, el doble factor.</p>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Persona</th><th>Rol</th><th>Doble factor</th><th>Último ingreso</th></tr></thead>
          <tbody>
            @foreach ($members as $member)
              <tr>
                <td><a class="user-cell" href="{{ route('admin.usuarios.show', $member) }}#equipo"><x-avatar :user="$member" :size="32" /><span><strong>{{ $member->name }}</strong><br><small class="hint">{{ $member->email }}</small></span></a></td>
                <td><span class="badge badge--nivel-1">{{ $member->team_role->label() }}</span></td>
                <td>@if ($member->hasTwoFactor())<span class="badge badge--ok">Activo</span>@else<span class="badge badge--error">Sin activar</span>@endif</td>
                <td style="white-space:nowrap">{{ $member->last_login_at?->timezone(config('tinku.timezone'))->format('d/m/Y H:i') ?? '—' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </section>

    <section class="wizard__panel">
      <h2>Qué puede hacer cada rol</h2>
      <div class="table-wrap">
        <table class="table team-matrix">
          <thead>
            <tr><th>Permiso</th>@foreach ($roles as $role)<th title="{{ $role->label() }}">{{ $role->shortLabel() }}</th>@endforeach</tr>
          </thead>
          <tbody>
            @foreach ($permissions as $permission)
              <tr>
                <td>{{ $permission->label() }}</td>
                @foreach ($roles as $role)
                  <td>@if ($role->grants($permission))<x-icon name="check" :size="18" label="Sí" />@else<span class="hint" aria-label="No">—</span>@endif</td>
                @endforeach
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <ul class="hint" style="margin:16px 0 0;padding-left:20px">
        @foreach ($roles as $role)<li><strong>{{ $role->label() }}:</strong> {{ $role->description() }}</li>@endforeach
      </ul>
    </section>
  </main>
</x-layout>
