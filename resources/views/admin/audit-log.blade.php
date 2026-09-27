<x-layout title="Auditoría" :noindex="true">
  <main class="container" style="padding:48px 0 104px">
    <p class="eyebrow">Administración</p>
    <h1 class="title">Auditoría de <span class="hl">cambios</span>.</h1>
    @include('admin.partials.nav')
    <section class="wizard__panel">
      <p>Cada alta, cambio y baja queda registrada con quién la hizo, cuándo, desde dónde y los valores anteriores y nuevos. Las contraseñas y los tokens nunca se guardan; la dirección y la cuenta de cobro figuran como dato protegido.</p>
      <form method="get" action="{{ route('admin.auditoria') }}" class="filters filters--4">
        <div class="field"><label for="tipo">Tabla</label>
          <select id="tipo" name="tipo"><option value="">Todas</option>
            @foreach (\App\Models\AuditLog::MODEL_LABELS as $class => $label)<option value="{{ $class }}" @selected(($filters['tipo'] ?? null) === $class)>{{ $label }}</option>@endforeach
          </select>
        </div>
        <div class="field"><label for="id">Número</label><input id="id" name="id" value="{{ $filters['id'] ?? '' }}" placeholder="ID del registro"></div>
        <div class="field"><label for="evento">Evento</label>
          <select id="evento" name="evento"><option value="">Todos</option>
            @foreach (\App\Models\AuditLog::EVENT_LABELS as $key => $label)<option value="{{ $key }}" @selected(($filters['evento'] ?? null) === $key)>{{ $label }}</option>@endforeach
          </select>
        </div>
        <div class="field"><label for="quien">Quién</label><input id="quien" name="quien" value="{{ $filters['quien'] ?? '' }}" placeholder="Nombre o email"></div>
        <div class="filters__actions"><button class="btn btn--secondary btn--sm" type="submit">Filtrá</button><a class="btn btn--ghost btn--sm" href="{{ route('admin.auditoria') }}">Limpiá</a></div>
      </form>
      @include('admin.partials.audit-table', ['logs' => $logs])
      <div style="margin-top:16px">{{ $logs->links() }}</div>
      <p class="hint" style="margin-top:16px">Se guarda {{ $retentionDays }} días. Horarios de Argentina.</p>
    </section>
  </main>
</x-layout>
