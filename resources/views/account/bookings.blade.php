<x-layout title="Tus reservas" :noindex="true">
  <main class="container wizard" style="max-width:820px">
    <p class="eyebrow">Tu cuenta</p>
    <h1 class="title">Tus <span class="hl">reservas</span>.</h1>
    @include('account.partials.nav')
    @error('booking')<p class="notice" role="alert">{{ $message }}</p>@enderror

    <section class="wizard__panel">
      <h2>Próximas</h2>
      @forelse ($upcoming as $booking)
        @include('account.partials.booking', ['booking' => $booking])
      @empty
        <p class="hint">No tenés reservas próximas. <a href="{{ route('home') }}#experiencias">Buscá una experiencia</a>.</p>
      @endforelse
    </section>

    @if ($past->isNotEmpty())
      <section class="wizard__panel">
        <h2>Historial</h2>
        @foreach ($past as $booking)
          @include('account.partials.booking', ['booking' => $booking])
        @endforeach
      </section>
    @endif
  </main>
</x-layout>
