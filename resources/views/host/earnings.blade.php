<x-layout title="Tus ganancias" :noindex="true">
  <main class="container wizard" style="max-width:920px">
    <p class="eyebrow">Anfitrión</p>
    <h1 class="title">Tus <span class="hl">ganancias</span>.</h1>
    @include('host.partials.nav')

    <div class="kpis kpis--static">
      <div class="kpi"><span class="kpi__val">{{ money($earned) }}</span><span class="kpi__lbl">Ganaste por experiencias realizadas</span></div>
      <div class="kpi"><span class="kpi__val">{{ money($toCollect) }}</span><span class="kpi__lbl">Por cobrar de reservas confirmadas</span></div>
      <div class="kpi"><span class="kpi__val">{{ money($requestedAmount) }}</span><span class="kpi__lbl">En {{ plural_es($requestedCount, 'pedido', 'pedidos') }} sin confirmar</span></div>
      <div class="kpi"><span class="kpi__val">{{ money($commission) }}</span><span class="kpi__lbl">Comisión de Tinku ({{ $profile->plan->commissionPercent() }}% en tu plan)</span></div>
    </div>

    <p class="notice">Tinku todavía no procesa pagos: los montos salen de tus reservas. Cuando conectemos los cobros, acá vas a ver cada liquidación y cuándo llega a tu cuenta.</p>

    <section class="wizard__panel">
      <h2>Por mes</h2>
      @if ($months->isEmpty())
        <p class="hint">Cuando hagas tu primera experiencia, acá ves cuánto ganaste cada mes.</p>
      @else
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>Mes</th><th>Reservas</th><th>Personas</th><th style="text-align:right">Ganaste</th></tr></thead>
            <tbody>
              @foreach ($months as $month)
                <tr><td>{{ $month['label'] }}</td><td>{{ $month['bookings'] }}</td><td>{{ $month['guests'] }}</td><td style="text-align:right;white-space:nowrap"><strong>{{ money($month['payout']) }}</strong></td></tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </section>

    <section class="wizard__panel">
      <h2>Reserva por reserva</h2>
      @if ($movements->isEmpty())
        <p class="hint">Todavía no tenés reservas confirmadas.</p>
      @else
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>Fecha</th><th>Experiencia</th><th>Personas</th><th style="text-align:right">Precio</th><th style="text-align:right">Comisión</th><th style="text-align:right">Recibís</th><th>Estado</th></tr></thead>
            <tbody>
              @foreach ($movements as $booking)
                <tr>
                  <td style="white-space:nowrap">{{ $booking->date->localStart()->format('d/m/Y') }}</td>
                  <td>{{ $booking->experience->title }}<br><small class="hint">{{ $booking->user->first_name }} · {{ $booking->code }}</small></td>
                  <td>{{ $booking->guests }}</td>
                  <td style="text-align:right;white-space:nowrap">{{ money($booking->subtotal) }}</td>
                  <td style="text-align:right;white-space:nowrap">− {{ money($booking->commission_amount) }}</td>
                  <td style="text-align:right;white-space:nowrap"><strong>{{ money($booking->host_payout) }}</strong></td>
                  <td>@if ($booking->status === \App\Enums\BookingStatus::Completed)<span class="badge badge--ok">Realizada</span>@else<span class="badge badge--nivel-1">Confirmada</span>@endif</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </section>
  </main>
</x-layout>
