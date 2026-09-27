<x-layout title="Seguridad" :noindex="true">
  <main class="container wizard" style="max-width:820px">
    <p class="eyebrow">Tu cuenta</p>
    <h1 class="title">Tu <span class="hl">seguridad</span>.</h1>
    @unless ($forced)
      @include('account.partials.nav')
    @endunless
    <section class="wizard__panel">
      <h2>Contraseña</h2>
      @if (auth()->user()->password_changed_at)
        <p>La cambiaste por última vez el {{ auth()->user()->password_changed_at->timezone(config('tinku.timezone'))->translatedFormat('j \\d\\e F \\d\\e Y') }}.</p>
      @endif
      @if ($forced)
        <p class="notice">Ingresaste con una contraseña de única vez. Elegí una nueva para seguir.</p>
      @endif
      <form method="post" action="{{ route('cuenta.seguridad.update') }}">
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
          <x-password-match />
        </div>
        <div class="wizard__actions">
          @if ($forced)
            <span></span>
          @else
            <a class="btn btn--ghost" href="{{ route('cuenta.perfil') }}">Cancelar</a>
          @endif
          <button class="btn btn--secondary" type="submit">Guardá la contraseña</button>
        </div>
      </form>
    </section>
  </main>
</x-layout>
