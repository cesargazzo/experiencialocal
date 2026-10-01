<x-layout title="Usuarios" :noindex="true">
  <main class="container" style="padding:48px 0 104px">
    <p class="eyebrow">Administración</p>
    <h1 class="title">Usuarios.</h1>
    @include('admin.partials.nav')

    <nav class="kpis" aria-label="Resumen de cuentas">
      @foreach ($kpis as $kpi)
        <a @class(['kpi', 'is-active' => $kpi['active']]) href="{{ route('admin.usuarios', $kpi['query']) }}" @if ($kpi['active']) aria-current="true" @endif>
          <span class="kpi__val">{{ number_format($kpi['value'], 0, ',', '.') }}</span>
          <span class="kpi__lbl">{{ $kpi['label'] }}</span>
        </a>
      @endforeach
    </nav>

    @if ($sharedDocuments->isNotEmpty())
      <section class="wizard__panel" role="alert">
        <h2>Documentos en más de una cuenta</h2>
        <p>El mismo documento aparece en estas cuentas. Revisalas y revocá la verificación que no corresponda.</p>
        @foreach ($sharedDocuments as $accounts)
          <p class="notice" style="margin:0 0 8px">@foreach ($accounts as $account)<a href="{{ route('admin.usuarios.show', $account) }}">{{ $account->name }}</a> ({{ $account->email }})@if (! $loop->last) · @endif @endforeach</p>
        @endforeach
      </section>
    @endif

    @if ($birthdaysThisWeek->isNotEmpty())
      @php($birthdaysToday = $birthdaysThisWeek->filter(fn ($user) => $user->birthdayOnOrAfter($today)->isSameDay($today)))
      <section class="wizard__panel birthdays">
        <h2><x-icon name="sparkle" :size="22" /> Cumpleaños</h2>
        @if ($birthdaysToday->isNotEmpty())
          <p class="birthdays__today"><strong>Hoy cumple{{ $birthdaysToday->count() > 1 ? 'n' : '' }}:</strong>
            @foreach ($birthdaysToday as $user)
              <a class="birthdays__person" href="{{ route('admin.usuarios.show', $user) }}"><x-avatar :user="$user" :size="28" /> {{ $user->name }} <small>(cumple {{ $user->ageTurningOn($today) }})</small></a>
            @endforeach
          </p>
        @else
          <p class="hint">Hoy no cumple nadie.</p>
        @endif
        <p class="hint" style="margin:12px 0 6px">Esta semana ({{ $today->copy()->startOfWeek()->translatedFormat('j M') }} al {{ $today->copy()->endOfWeek()->translatedFormat('j M') }}):</p>
        <ul class="birthdays__week">
          @foreach ($birthdaysThisWeek as $user)
            @php($birthday = $user->birthdayOnOrAfter($weekStart))
            <li @class(['is-today' => $birthday->isSameDay($today), 'is-past' => $birthday->lt($today)])>
              <span>{{ Str::ucfirst($birthday->translatedFormat('D j')) }}</span>
              <a href="{{ route('admin.usuarios.show', $user) }}">{{ $user->name }}</a>
              <small class="hint">cumple {{ $user->ageTurningOn($birthday) }}</small>
            </li>
          @endforeach
        </ul>
      </section>
    @endif

    <section class="wizard__panel">
      <form method="get" action="{{ route('admin.usuarios') }}" class="filters filters--users">
        <div class="field"><label for="q">Buscar</label><input id="q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nombre o email"></div>
        <div class="field"><label for="dni">DNI o documento</label><input id="dni" name="dni" value="{{ $filters['dni'] ?? '' }}" inputmode="numeric" autocomplete="off" placeholder="Número exacto"></div>
        <div class="field"><label for="nivel">Nivel</label>
          <select id="nivel" name="nivel">
            <option value="">Todos</option>
            <option value="verificada" @selected(($filters['nivel'] ?? null) === 'verificada')>2 o más · Identidad verificada</option>
            @foreach ($levels as $level)<option value="{{ $level->value }}" @selected(($filters['nivel'] ?? null) === (string) $level->value)>{{ $level->value }} · {{ $level->label() }}</option>@endforeach
          </select>
        </div>
        <div class="field"><label for="rol">Rol</label>
          <select id="rol" name="rol">
            <option value="">Todos</option>
            <option value="anfitrion" @selected(($filters['rol'] ?? null) === 'anfitrion')>Anfitriones</option>
            <option value="admin" @selected(($filters['rol'] ?? null) === 'admin')>Equipo de Tinku</option>
            <option value="suspendida" @selected(($filters['rol'] ?? null) === 'suspendida')>Suspendidas</option>
          </select>
        </div>
        <div class="field"><label for="provincia">Provincia</label>
          <select id="provincia" name="provincia">
            <option value="">Todas</option>
            @foreach ($provinces as $province)<option value="{{ $province->id }}" @selected((int) ($filters['provincia'] ?? 0) === $province->id)>{{ $province->name }}</option>@endforeach
          </select>
        </div>
        <div class="field"><label for="alta">Registrados</label>
          <select id="alta" name="alta">
            <option value="">Siempre</option>
            @foreach ($signupPeriods as $key => $period)<option value="{{ $key }}" @selected(($filters['alta'] ?? null) === $key)>{{ $period['label'] }}</option>@endforeach
          </select>
        </div>
        <div class="field"><label for="ingreso">Ingresaron</label>
          <select id="ingreso" name="ingreso">
            <option value="">Cuando sea</option>
            @foreach ($loginPeriods as $key => $period)<option value="{{ $key }}" @selected(($filters['ingreso'] ?? null) === $key)>{{ $period['label'] }}</option>@endforeach
            <option value="nunca" @selected(($filters['ingreso'] ?? null) === 'nunca')>Nunca</option>
          </select>
        </div>
        <div class="field"><label for="cumple">Cumpleaños</label>
          <select id="cumple" name="cumple">
            <option value="">Todos</option>
            <option value="hoy" @selected(($filters['cumple'] ?? null) === 'hoy')>Cumplen hoy</option>
            <option value="semana" @selected(($filters['cumple'] ?? null) === 'semana')>Cumplen esta semana</option>
          </select>
        </div>
        <div class="field"><label for="orden">Orden</label>
          <select id="orden" name="orden">
            @foreach ($sorts as $key => $label)<option value="{{ $key }}" @selected(($filters['orden'] ?? (isset($filters['ingreso']) ? 'ingreso' : 'alta')) === $key)>{{ $label }}</option>@endforeach
          </select>
        </div>
        <div class="filters__actions"><button class="btn btn--secondary btn--sm" type="submit">Filtrá</button><a class="btn btn--ghost btn--sm" href="{{ route('admin.usuarios') }}">Limpiá</a></div>
      </form>
      <p class="hint" style="margin:4px 0 16px">{{ plural_es($users->total(), 'cuenta', 'cuentas') }}. El DNI no se guarda: se busca por el número exacto.</p>

      @php($canVerify = auth()->user()->can('team.users.verify'))
      <form method="post" action="{{ route('admin.usuarios.validar') }}" x-data="{ selected: [], all: @js($users->pluck('id')) }">
        @csrf
        @if ($errors->has('users'))
          <div class="notice" role="alert">@foreach ($errors->get('users') as $problem)<p style="margin:0">{{ $problem }}</p>@endforeach</div>
        @endif
        @error('reason')<p class="error" style="display:block">{{ $message }}</p>@enderror
        @if ($canVerify)
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
        @endif

        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                @if ($canVerify)<th><input type="checkbox" aria-label="Elegir todas" x-on:change="selected = $event.target.checked ? [...all] : []" x-bind:checked="selected.length === all.length && all.length > 0"></th>@endif
                <th>Cuenta</th><th>Nivel</th><th>Rol</th><th>Alta</th><th>Último ingreso</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($users as $user)
                <tr @if ($user->isSuspended()) class="is-muted" @endif>
                  @if ($canVerify)<td><input type="checkbox" name="users[]" value="{{ $user->id }}" x-model.number="selected" aria-label="Elegir {{ $user->name }}"></td>@endif
                  <td>
                    <a class="user-cell" href="{{ route('admin.usuarios.show', $user) }}">
                      <x-avatar :user="$user" :size="32" />
                      <span><strong>{{ $user->name }}</strong><br><small class="hint">{{ $user->email }}@if ($user->province) · {{ $user->city ? $user->city.', ' : '' }}{{ $user->province->name }}@endif</small></span>
                    </a>
                  </td>
                  <td><x-verification-badge :level="$user->verification_level" full /></td>
                  <td>
                    @if ($user->team_role)<span class="badge badge--nivel-1">{{ $user->team_role->label() }}</span>@endif
                    @if ($user->hostProfile)<span class="badge badge--nivel-1">Anfitrión</span>@endif
                    @if ($user->isSuspended())<span class="badge badge--error">Suspendida</span>@endif
                  </td>
                  <td style="white-space:nowrap">{{ $user->created_at->timezone(config('tinku.timezone'))->format('d/m/Y') }}</td>
                  <td style="white-space:nowrap">
                    {{ $user->last_login_at?->timezone(config('tinku.timezone'))->format('d/m/Y H:i') ?? '—' }}
                    @if ($user->last_seen_at)<br><small class="hint">Activa {{ $user->last_seen_at->diffForHumans() }}</small>@endif
                  </td>
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
