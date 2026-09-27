<x-layout title="Registro de seguridad" :noindex="true">
  <main class="container" style="padding:48px 0 104px">
    <p class="eyebrow">Administración</p>
    <h1 class="title">Registro de <span class="hl">seguridad</span>.</h1>
    @include('admin.partials.nav')

    <section class="stats" style="margin-bottom:24px">
      @foreach ([
        ['login.failed', 'ingresos fallidos'],
        ['login.locked', 'bloqueos por intentos'],
        ['probe.suspicious', 'rastreos sospechosos'],
        ['error.server', 'errores del servidor'],
      ] as [$type, $label])
        <div class="stat">
          <div class="stat__val" @if (($counts[$type] ?? 0) > 0 && $type !== 'login.failed') style="color:var(--texto-coral)" @endif>{{ $counts[$type] ?? 0 }}</div>
          <div class="stat__lbl">{{ $label }} en las últimas 24 h</div>
        </div>
      @endforeach
    </section>

    @if ($suspiciousIps->isNotEmpty())
      <section class="wizard__panel" style="margin-bottom:24px">
        <h2>Direcciones IP con más señales de riesgo (24 h)</h2>
        <p>Muchos intentos desde una misma IP, o contra muchas cuentas distintas, suelen ser un ataque automatizado.</p>
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>IP</th><th>Eventos</th><th>Cuentas distintas</th><th>Último</th><th></th></tr></thead>
            <tbody>
              @foreach ($suspiciousIps as $row)
                <tr>
                  <td><code>{{ $row->ip }}</code></td>
                  <td>{{ $row->total }}</td>
                  <td>{{ $row->accounts }}</td>
                  <td>{{ \Illuminate\Support\Carbon::parse($row->last_seen)->timezone(config('tinku.timezone'))->format('d/m H:i') }}</td>
                  <td><a class="link" href="{{ route('admin.seguridad', ['ip' => $row->ip]) }}">Ver eventos</a></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </section>
    @endif

    <section class="wizard__panel">
      <form method="get" action="{{ route('admin.seguridad') }}" class="filters">
        <div class="field"><label for="grupo">Tipo</label>
          <select id="grupo" name="grupo">
            <option value="">Todos</option>
            @foreach ($groups as $group)
              <option value="{{ $group }}" @selected(($filters['grupo'] ?? null) === $group)>{{ ['accesos' => 'Accesos', 'contrasenas' => 'Contraseñas', 'admin' => 'Administración', 'amenazas' => 'Señales de riesgo', 'errores' => 'Errores'][$group] }}</option>
            @endforeach
          </select>
        </div>
        <div class="field"><label for="ip">IP</label><input id="ip" name="ip" value="{{ $filters['ip'] ?? '' }}" placeholder="200.1.2.3"></div>
        <div class="field"><label for="email">Email</label><input id="email" name="email" value="{{ $filters['email'] ?? '' }}" placeholder="parte del email"></div>
        <div class="filters__actions"><button class="btn btn--secondary btn--sm" type="submit">Filtrá</button><a class="btn btn--ghost btn--sm" href="{{ route('admin.seguridad') }}">Limpiá</a></div>
      </form>

      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Cuándo</th><th>Evento</th><th>Cuenta</th><th>IP</th><th>Detalle</th></tr></thead>
          <tbody>
            @forelse ($events as $event)
              <tr>
                <td style="white-space:nowrap">{{ $event->created_at->timezone(config('tinku.timezone'))->format('d/m/Y H:i:s') }}</td>
                <td><span class="badge badge--sev-{{ $event->severity }}">{{ $event->label() }}</span></td>
                <td>{{ $event->user?->name ?? '—' }}@if ($event->email)<br><small class="hint">{{ $event->email }}</small>@endif</td>
                <td><a class="link" href="{{ route('admin.seguridad', ['ip' => $event->ip]) }}"><code>{{ $event->ip }}</code></a></td>
                <td>
                  <small class="hint">{{ $event->method }} /{{ $event->path }}</small>
                  @if ($event->metadata)
                    <details><summary class="hint" style="cursor:pointer">Ver más</summary><pre class="pre">{{ json_encode($event->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre><small class="hint">{{ Str::limit($event->user_agent, 160) }}</small></details>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="5" class="hint">No hay eventos con esos filtros.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div style="margin-top:16px">{{ $events->links() }}</div>
      <p class="hint" style="margin-top:16px">Los eventos se guardan {{ $retentionDays }} días. Horarios de Argentina.</p>
    </section>
  </main>
</x-layout>
