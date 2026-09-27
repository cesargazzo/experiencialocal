<x-layout title="Recuperá tu contraseña" :noindex="true">
  <main class="container wizard" style="max-width:560px">
    <p class="eyebrow">Tu cuenta</p>
    <h1 class="title">Recuperá tu <span class="hl">contraseña</span>.</h1>
    <section class="wizard__panel">
      <p>Escribí el email de tu cuenta y te mandamos un enlace para elegir una contraseña nueva.</p>
      <form method="post" action="{{ route('password.email') }}">
        @csrf
        <div class="field">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
          @error('email')<span class="error" style="display:block">{{ $message }}</span>@enderror
        </div>
        <div class="wizard__actions">
          <a class="btn btn--ghost" href="{{ route('login') }}">Volvé a ingresar</a>
          <button class="btn btn--secondary" type="submit">Mandame el enlace</button>
        </div>
      </form>
    </section>
  </main>
</x-layout>
