<x-layout title="Favoritas" :noindex="true">
  <main class="container wizard" style="max-width:1100px">
    <p class="eyebrow">Tu cuenta</p>
    <h1 class="title">Tus <span class="hl">favoritas</span>.</h1>
    @include('account.partials.nav')

    @if ($experiences->isEmpty())
      <section class="wizard__panel">
        <p>Todavía no guardaste ninguna. Tocá el corazón de una experiencia para tenerla a mano acá.</p>
        <a class="btn btn--secondary" href="{{ route('home') }}#experiencias">Mirá las experiencias</a>
      </section>
    @else
      <div class="grid">
        @foreach ($experiences as $e)
          <x-experience-card :experience="$e" />
        @endforeach
      </div>
    @endif
  </main>
</x-layout>
