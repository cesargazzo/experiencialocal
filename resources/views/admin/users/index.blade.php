<x-layout title="Usuarios" :noindex="true">
  <main class="container" style="padding:48px 0 104px">
    <p class="eyebrow">Administración</p>
    <h1 class="title">Usuarios.</h1>
    @include('admin.partials.nav')

    <section class="wizard__panel">
      <form method="get" action="{{ route('admin.usuarios') }}" class="filters">
        <div class="field"><label for="q">Buscar</label><input id="q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nombre o email"></div>
        <div class="field"><label for="nivel">Nivel</label>
          <select id="nivel" name="nivel">
            <option value="">Todos</option>
            @foreach ($levels as $level)<option value="{{ $level->value }}" @selected(($filters['nivel'] ?? null) === (string) $level->value)>{{ $level->value }} · {{ $level->label() }}</option>@endforeach
          </select>
        </div>
        <div class="field"><label for="rol">Rol</label>
          <select id="rol" name="rol">
            <option value="">Todos</option>
            <option value="anfitrion" @selected(($filters['rol'] ?? null) === 'anfitrion')>Anfitriones</option>
            <option value="admin" @selected(($filters['rol'] ?? null) === 'admin')>Administradores</option>
            <option value="suspendida" @selected(($filters['rol'] ?? null) === 'suspendida')>Suspendidas</option>
          </select>
        </div>
        <div class="filters__actions"><button class="btn btn--secondary btn--sm" type="submit">Filtrá</button><a class="btn btn--ghost btn--sm" href="{{ route('admin.usuarios') }}">Limpiá</a></div>
      </form>

      <form method="post" action="{{ route('admin.usuarios.validar') }}" x-data="{ selected: [], all: @js($users->pluck('id')) }">
        @csrf
        @error('users')<p class="error" style="display:block">{{ $message }}</p>@enderror
        @error('reason')<p class="error" style="display:block">{{ $message }}</p>@enderror
        <div class="bulk" x-show="selected.length > 0" x-cloak>
          <strong x-text="selected.length === 1 ? '1 cuenta elegida' : selected.length + ' cuentas elegidas'"></strong>
          <label class="sr-only" for="bulk-level">Nivel</label>
          <select id="bulk-level" name="level" class="inline-input">
            <option value="2">Validar hasta nivel 2 · Documento</option>
            <option value="3">Validar hasta nivel 3 · Identidad y domicilio</option>
            <option value="1">Validar hasta nivel 1 · Contacto confirmado</option>
          </select>
          <label class="sr-only" for="bulk-reason">Motivo</label>
          <input id="bulk-reason" name="reason" class="inline-input" placeholder="Motivo (queda registrado)" required minlength="5" maxlength="300" style="flex:1;min-width:200px">
          <button class="btn btn--secondary btn--sm" type="submit">Validá</button>
        </div>

        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th><input type="checkbox" aria-label="Elegir todas" x-on:change="selected = $event.target.checked ? [...all] : []" x-bind:checked="selected.length === all.length && all.length > 0"></th>
                <th>Cuenta</th><th>Nivel</th><th>Rol</th><th>Alta</th><th>Último ingreso</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($users as $user)
                <tr @if ($user->isSuspended()) class="is-muted" @endif>
                  <td><input type="checkbox" name="users[]" value="{{ $user->id }}" x-model.number="selected" aria-label="Elegir {{ $user->name }}"></td>
                  <td>
                    <a class="user-cell" href="{{ route('admin.usuarios.show', $user) }}">
                      <x-avatar :user="$user" :size="32" />
                      <span><strong>{{ $user->name }}</strong><br><small class="hint">{{ $user->email }}</small></span>
                    </a>
                  </td>
                  <td><x-verification-badge :level="$user->verification_level" full /></td>
                  <td>
                    @if ($user->is_admin)<span class="badge badge--nivel-1">Admin</span>@endif
                    @if ($user->hostProfile)<span class="badge badge--nivel-1">Anfitrión</span>@endif
                    @if ($user->isSuspended())<span class="badge badge--error">Suspendida</span>@endif
                  </td>
                  <td style="white-space:nowrap">{{ $user->created_at->timezone(config('tinku.timezone'))->format('d/m/Y') }}</td>
                  <td style="white-space:nowrap">{{ $user->last_login_at ? \Illuminate\Support\Carbon::parse($user->last_login_at)->timezone(config('tinku.timezone'))->format('d/m/Y H:i') : '—' }}</td>
                </tr>
              @empty
                <tr><td colspan="6" class="hint">No hay cuentas con esos filtros.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </form>
      <div style="margin-top:16px">{{ $users->links() }}</div>
    </section>
  </main>
</x-layout>
