<x-layout title="Anunciantes" :noindex="true">
  <main class="container wizard" style="max-width:1040px">
    <p class="eyebrow">Administración</p>
    <h1 class="title"><span class="hl">Anunciantes</span>.</h1>
    @include('admin.partials.nav')

    <nav class="chips" aria-label="Estado">
      <a @class(['chip', 'is-active' => ! $status]) href="{{ route('admin.anunciantes') }}">Todas ({{ $counts->sum() }})</a>
      @foreach (\App\Models\AdvertiserInquiry::STATUSES as $key => $label)
        <a @class(['chip', 'is-active' => $status === $key]) href="{{ route('admin.anunciantes', ['estado' => $key]) }}">{{ $label }} ({{ $counts[$key] ?? 0 }})</a>
      @endforeach
      <a class="chip" href="{{ route('mediakit') }}" target="_blank" rel="noopener">Ver el mediakit</a>
    </nav>

    <section class="wizard__panel">
      @forelse ($inquiries as $inquiry)
        <article class="review-item" style="grid-template-columns:1fr">
          <div>
            <p class="hint" style="margin:0 0 6px">{{ $inquiry->created_at->timezone(config('tinku.timezone'))->format('d/m/Y H:i') }} · <span class="badge {{ ['new' => 'badge--sev-warning', 'contacted' => 'badge--nivel-1', 'closed' => 'badge--ok'][$inquiry->status] }}">{{ \App\Models\AdvertiserInquiry::STATUSES[$inquiry->status] }}</span>@if ($inquiry->handler) · {{ $inquiry->handler->name }}@endif</p>
            <h3 style="margin:0 0 4px">{{ $inquiry->company }}</h3>
            <p style="margin:0 0 6px">{{ $inquiry->name }} · <a href="mailto:{{ $inquiry->email }}">{{ $inquiry->email }}</a>@if ($inquiry->phone) · {{ $inquiry->phone }}@endif</p>
            @if ($inquiry->formats)<p class="hint" style="margin:0 0 6px">{{ collect($inquiry->formats)->map(fn ($f) => \App\Models\AdvertiserInquiry::FORMATS[$f] ?? $f)->join(' · ') }}</p>@endif
            @if ($inquiry->budget)<p class="hint" style="margin:0 0 6px">Presupuesto: {{ \App\Models\AdvertiserInquiry::BUDGETS[$inquiry->budget] ?? $inquiry->budget }}</p>@endif
            <blockquote class="host-booking__note" style="margin:0 0 10px;white-space:pre-line">{{ $inquiry->message }}</blockquote>
            <form method="post" action="{{ route('admin.anunciantes.update', $inquiry) }}" style="display:flex;gap:8px;flex-wrap:wrap">
              @csrf
              @method('put')
              @foreach (\App\Models\AdvertiserInquiry::STATUSES as $key => $label)
                @continue($key === $inquiry->status)
                <button class="btn btn--ghost btn--sm" name="status" value="{{ $key }}">Marcar como {{ Str::lower($label) }}</button>
              @endforeach
            </form>
          </div>
        </article>
      @empty
        <p class="hint">Todavía no hay consultas. Compartí el mediakit: <a href="{{ route('mediakit') }}">{{ route('mediakit') }}</a></p>
      @endforelse
      {{ $inquiries->links() }}
    </section>
  </main>
</x-layout>
