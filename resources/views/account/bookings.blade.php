<x-layout :title="__('Tus reservas')" :noindex="true">
  <main class="container wizard" style="max-width:820px">
    <p class="eyebrow">{{ __('Tu cuenta') }}</p>
    <h1 class="title">{!! __('Tus <span class="hl">reservas</span>.') !!}</h1>
    @include('account.partials.nav')
    @error('booking')<p class="notice" role="alert">{{ $message }}</p>@enderror
    @error('review')<p class="notice" role="alert">{{ $message }}</p>@enderror

    <section class="wizard__panel">
      <h2>{{ __('Próximas') }}</h2>
      @forelse ($upcoming as $booking)
        @include('account.partials.booking', ['booking' => $booking])
      @empty
        <p class="hint">{{ __('No tenés reservas próximas.') }} <a href="{{ route('home') }}#experiencias">{{ __('Buscá una experiencia') }}</a>.</p>
      @endforelse
    </section>

    @if ($past->isNotEmpty())
      <section class="wizard__panel">
        <h2>{{ __('Historial') }}</h2>
        @foreach ($past as $booking)
          @include('account.partials.booking', ['booking' => $booking])
        @endforeach
      </section>
    @endif
  </main>
</x-layout>
