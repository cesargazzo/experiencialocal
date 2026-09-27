<x-layout title="Aceptá los términos" :noindex="true">
  <main class="container wizard" style="max-width:820px">
    <p class="eyebrow">Tu cuenta</p>
    <h1 class="title">{{ $hadAcceptedBefore ? 'Actualizamos los' : 'Aceptá los' }} <span class="hl">términos</span>.</h1>
    <section class="wizard__panel">
      <p>
        @if ($hadAcceptedBefore)
          Publicamos la versión {{ $terms->version }} de los términos y condiciones. Para seguir usando Tinku, leela y aceptala.
        @else
          Para seguir usando Tinku, leé y aceptá los términos y condiciones (versión {{ $terms->version }}).
        @endif
      </p>
      @if ($terms->changes_summary)
        <p class="notice"><strong>Qué cambió:</strong> {{ $terms->changes_summary }}</p>
      @endif
      <div class="terms-scroll terms-text" tabindex="0" aria-label="Texto de los términos">{{ $terms->body }}</div>
      <form method="post" action="{{ route('terminos.aceptar.store') }}">
        @csrf
        <input type="hidden" name="terms_version_id" value="{{ $terms->id }}">
        <label class="toggle-row terms-accept">
          <input type="checkbox" name="accept" value="1" required>
          <span>Leí y acepto los términos y condiciones (versión {{ $terms->version }}).</span>
        </label>
        @error('accept')<span class="error" style="display:block">{{ $message }}</span>@enderror
        @error('terms_version_id')<span class="error" style="display:block">{{ $message }}</span>@enderror
        <div class="wizard__actions">
          <button class="btn btn--ghost" type="submit" form="logout-form">Salir</button>
          <button class="btn btn--primary" type="submit">Acepto</button>
        </div>
      </form>
      <form id="logout-form" method="post" action="{{ route('logout') }}">@csrf</form>
    </section>
  </main>
</x-layout>
