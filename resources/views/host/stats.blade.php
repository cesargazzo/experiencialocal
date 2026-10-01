<x-layout title="Tus estadísticas" :noindex="true">
  <main class="container wizard" style="max-width:1100px">
    <p class="eyebrow">Anfitrión</p>
    <h1 class="title">Cómo te <span class="hl">va</span>.</h1>
    @include('host.partials.nav')

    <nav class="chips" aria-label="Período">
      @foreach ($periods as $period)
        <a @class(['chip', 'is-active' => $days === $period]) href="{{ route('anfitrion.estadisticas', ['dias' => $period]) }}" @if ($days === $period) aria-current="true" @endif>Últimos {{ $period }} días</a>
      @endforeach
    </nav>

    <div class="kpis kpis--static">
      <div class="kpi"><span class="kpi__val">{{ number_format($totals['views'], 0, ',', '.') }}</span><span class="kpi__lbl">Visitas a tus experiencias</span></div>
      <div class="kpi"><span class="kpi__val">{{ $totals['questions'] }}</span><span class="kpi__lbl">Consultas por mensaje</span></div>
      <div class="kpi"><span class="kpi__val">{{ $totals['requests'] }}</span><span class="kpi__lbl">Pedidos de reserva</span></div>
      <div class="kpi"><span class="kpi__val">{{ \App\Http\Controllers\HostStatsController::conversion($totals['views'], $totals['requests']) }}</span><span class="kpi__lbl">De las visitas terminan en pedido</span></div>
    </div>

    <section class="wizard__panel">
      <h2>Por experiencia</h2>
      @if ($rows->isEmpty())
        <p class="hint">Cuando publiques tu primera experiencia, acá ves cuánta gente la mira y la reserva.</p>
      @else
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>Experiencia</th><th>Visitas</th><th>Consultas</th><th>Pedidos</th><th>Confirmadas</th><th>Conversión</th><th>Favoritas</th><th>Puntaje</th></tr></thead>
            <tbody>
              @foreach ($rows as $row)
                <tr>
                  <td><a href="{{ route('experiencias.show', $row['experience']) }}">{{ $row['experience']->title }}</a>@unless ($row['experience']->isPublished())<br><small class="hint">{{ $row['experience']->statusLabel() }}</small>@endunless</td>
                  <td>{{ number_format($row['views'], 0, ',', '.') }}</td>
                  <td>{{ $row['questions'] }}</td>
                  <td>{{ $row['requests'] }}</td>
                  <td>{{ $row['confirmed'] }}</td>
                  <td>{{ \App\Http\Controllers\HostStatsController::conversion($row['views'], $row['requests']) }}</td>
                  <td>{{ $row['favorites'] }}</td>
                  <td>{{ $row['experience']->reviews_count ? number_format($row['experience']->rating_avg, 1, ',', '.').' ('.$row['experience']->reviews_count.')' : '—' }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <p class="hint" style="margin-top:12px">Una visita es una persona que abrió la ficha ese día; no cuentan las tuyas, las del equipo ni los buscadores. Las favoritas son el total de personas que la guardaron.</p>
      @endif
    </section>
  </main>
</x-layout>
