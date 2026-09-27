<x-layout :title="$terms?->title ?? 'Términos y condiciones'" description="Términos y condiciones para usar Tinku.">
  <main class="container wizard" style="max-width:820px">
    <p class="eyebrow">Tinku</p>
    <h1 class="title">{{ $terms?->title ?? 'Términos y condiciones' }}</h1>
    @if ($terms)
      <p class="hint">Versión {{ $terms->version }} · vigente desde el {{ $terms->published_at->timezone(config('tinku.timezone'))->format('d/m/Y') }}</p>
      @if ($terms->changes_summary)
        <p class="notice"><strong>Qué cambió:</strong> {{ $terms->changes_summary }}</p>
      @endif
      <section class="wizard__panel">
        <div class="terms-text">{{ $terms->body }}</div>
      </section>
      @if ($history->count() > 1)
        <section class="wizard__panel">
          <h2>Versiones anteriores</h2>
          <ul>
            @foreach ($history as $version)
              <li><a href="{{ route('terminos.version', $version) }}">Versión {{ $version->version }}</a> · desde el {{ $version->published_at->timezone(config('tinku.timezone'))->format('d/m/Y') }}</li>
            @endforeach
          </ul>
        </section>
      @endif
    @else
      <section class="wizard__panel"><p>Estamos terminando los términos y condiciones. Muy pronto los vas a encontrar acá.</p></section>
    @endif
  </main>
</x-layout>
