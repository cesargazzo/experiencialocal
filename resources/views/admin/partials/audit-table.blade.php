<div class="table-wrap">
  <table class="table">
    <thead><tr><th>Cuándo</th><th>Qué</th><th>Quién</th><th>Cambios</th></tr></thead>
    <tbody>
      @forelse ($logs as $log)
        <tr>
          <td style="white-space:nowrap">{{ $log->created_at->timezone(config('tinku.timezone'))->format('d/m/Y H:i:s') }}<br><small class="hint">{{ $log->source }}{{ $log->ip ? ' · '.$log->ip : '' }}</small></td>
          <td>
            <span @class(['badge', 'badge--ok' => $log->event === 'created', 'badge--sev-warning' => $log->event === 'updated' || $log->event === 'interests.updated', 'badge--sev-danger' => $log->event === 'deleted'])>{{ $log->eventLabel() }}</span><br>
            <a class="link" href="{{ route('admin.auditoria', ['tipo' => $log->auditable_type, 'id' => $log->auditable_id]) }}">{{ $log->modelLabel() }} #{{ $log->auditable_id }}</a>
          </td>
          <td>{{ $log->user?->name ?? ($log->source === 'consola' ? 'Sistema' : 'Sin sesión') }}@if ($log->user)<br><small class="hint">{{ $log->user->email }}</small>@endif</td>
          <td>
            <details>
              <summary class="hint" style="cursor:pointer">{{ count($log->changes()) }} {{ count($log->changes()) === 1 ? 'campo' : 'campos' }}</summary>
              <table class="diff">
                @foreach ($log->changes() as $field => [$before, $after])
                  <tr>
                    <th>{{ $field }}</th>
                    <td class="diff__old">{{ is_scalar($before) || $before === null ? var_export($before, true) : json_encode($before, JSON_UNESCAPED_UNICODE) }}</td>
                    <td class="diff__new">{{ is_scalar($after) || $after === null ? var_export($after, true) : json_encode($after, JSON_UNESCAPED_UNICODE) }}</td>
                  </tr>
                @endforeach
              </table>
            </details>
          </td>
        </tr>
      @empty
        <tr><td colspan="4" class="hint">No hay cambios registrados con esos filtros.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
