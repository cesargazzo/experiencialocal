<x-layout title="Registro de errores" :noindex="true">
  <main class="container" style="padding:48px 0 104px">
    <p class="eyebrow">Administración</p>
    <h1 class="title">Registro de <span class="hl">errores</span>.</h1>
    @include('admin.partials.nav')

    <section class="wizard__panel">
      <p>Lo que Laravel anota en <code>storage/logs</code>: errores, avisos y mensajes del sistema. Se muestran las últimas 200 entradas del final del archivo. Puede contener datos técnicos: no lo compartas.</p>
      @if ($files->isEmpty())
        <p class="hint">No hay archivos de registro todavía. Eso es buena señal.</p>
      @else
        <form method="get" action="{{ route('admin.registro') }}" class="filters filters--4">
          <div class="field"><label for="archivo">Archivo</label>
            <select id="archivo" name="archivo">
              @foreach ($files as $f)<option value="{{ $f['name'] }}" @selected($file === $f['name'])>{{ $f['name'] }} · {{ number_format($f['size'] / 1024, 0, ',', '.') }} KB</option>@endforeach
            </select>
          </div>
          <div class="field"><label for="nivel">Nivel</label>
            <select id="nivel" name="nivel">
              <option value="">Todos</option>
              @foreach ($levels as $level)<option value="{{ $level }}" @selected(($filters['nivel'] ?? null) === $level)>{{ ucfirst($level) }}</option>@endforeach
            </select>
          </div>
          <div class="field"><label for="q">Buscar</label><input id="q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Texto del error"></div>
          <span></span>
          <div class="filters__actions"><button class="btn btn--secondary btn--sm" type="submit">Filtrá</button><a class="btn btn--ghost btn--sm" href="{{ route('admin.registro.descargar', $file) }}">Descargá</a></div>
        </form>

        @forelse ($entries as $entry)
          @php($severity = in_array($entry['level'], ['emergency', 'alert', 'critical', 'error'], true) ? 'danger' : ($entry['level'] === 'warning' ? 'warning' : 'info'))
          <details class="log-entry">
            <summary>
              <span class="badge badge--sev-{{ $severity }}">{{ strtoupper($entry['level']) }}</span>
              <small class="hint">{{ $entry['date']?->timezone(config('tinku.timezone'))->format('d/m/Y H:i:s') }}</small>
              <span class="log-entry__message">{{ $entry['message'] }}</span>
            </summary>
            @if ($entry['details'])<pre class="log-entry__details">{{ $entry['details'] }}</pre>@endif
          </details>
        @empty
          <p class="hint">No hay entradas con esos filtros.</p>
        @endforelse
      @endif
    </section>
  </main>
</x-layout>
