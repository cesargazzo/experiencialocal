<x-layout title="Denuncias" :noindex="true">
  <main class="container wizard" style="max-width:920px">
    <p class="eyebrow">Administración</p>
    <h1 class="title"><span class="hl">Denuncias</span>.</h1>
    @include('admin.partials.nav')

    <section class="wizard__panel">
      <p>Mensajes que alguien denunció. Se muestra solo el mensaje denunciado, no la conversación, y cada consulta queda en el registro de seguridad. Si corresponde, suspendé la cuenta desde su detalle.</p>
      @forelse ($reports as $report)
        <article class="review-item" style="grid-template-columns:1fr">
          <div>
            <p class="hint" style="margin:0 0 6px">
              {{ $report->reported_by ? 'Denunciado' : 'Marcado por la revisión automática' }} {{ $report->reported_at->diffForHumans() }} · en {{ $report->conversation->experience->title }}
              @if ($report->hidden_at)<span class="badge badge--error">Retenido: no lo ve quien lo recibió</span>@endif
            </p>
            <p style="margin:0 0 6px"><strong>Escribió:</strong> <x-admin-user-link :user="$report->sender" :with-email="true" /></p>
            <blockquote class="host-booking__note" style="margin:0 0 6px;white-space:pre-line">{{ $report->body }}</blockquote>
            <p style="margin:0 0 10px"><strong>Motivo:</strong> {{ $report->report_reason }}</p>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
              <form method="post" action="{{ route('admin.denuncias.ocultar', $report) }}">@csrf<button class="btn btn--tertiary btn--sm">Ocultarlo</button></form>
              <form method="post" action="{{ route('admin.denuncias.descartar', $report) }}">@csrf<button class="btn btn--ghost btn--sm">{{ $report->hidden_at ? 'Estaba bien: liberarlo' : 'Descartar la denuncia' }}</button></form>
            </div>
          </div>
        </article>
      @empty
        <p class="hint">No hay denuncias.</p>
      @endforelse
      <div style="margin-top:16px">{{ $reports->links() }}</div>
    </section>
  </main>
</x-layout>
