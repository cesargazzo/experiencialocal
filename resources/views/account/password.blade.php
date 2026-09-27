<x-layout title="Cambiá tu contraseña">
  <main class="container wizard" style="max-width:560px">
    <p class="eyebrow">Tu cuenta</p>
    <h1 class="title">Cambiá tu <span class="hl">contraseña</span>.</h1>
    <section class="wizard__panel">
      @if ($forced)
        <p class="notice">Ingresaste con una contraseña de única vez. Elegí una nueva para seguir.</p>
      @endif
      <form method="post" action="{{ route('cuenta.contrasena.update') }}">
        @csrf
        @method('put')
        <div class="field">
          <label for="current_password">{{ $forced ? 'Contraseña de única vez' : 'Contraseña actual' }}</label>
          <x-password-input name="current_password" autocomplete="current-password" />
          @error('current_password')<span class="error" style="display:block">{{ $message }}</span>@enderror
        </div>
        <div class="field">
          <label for="password">Contraseña nueva</label>
          <x-password-input name="password" autocomplete="new-password" aria-describedby="password-requirements" />
          @error('password')<span class="error" style="display:block">{{ $message }}</span>@enderror
          <x-password-requirements id="password-requirements" />
        </div>
        <div class="field">
          <label for="password_confirmation">Repetí la contraseña nueva</label>
          <x-password-input name="password_confirmation" autocomplete="new-password" />
        </div>
        <div class="wizard__actions">
          @if ($forced)
            <span></span>
          @else
            <a class="btn btn--ghost" href="{{ route('verificacion') }}">Cancelar</a>
          @endif
          <button class="btn btn--secondary" type="submit">Guardá la contraseña</button>
        </div>
      </form>
    </section>
  </main>
</x-layout>
