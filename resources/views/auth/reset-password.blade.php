<x-layout :title="__('Elegí una contraseña nueva')" :noindex="true">
  <main class="container wizard" style="max-width:560px">
    <p class="eyebrow">{{ __('Tu cuenta') }}</p>
    <h1 class="title">{!! __('Elegí una contraseña <span class="hl">nueva</span>.') !!}</h1>
    <section class="wizard__panel">
      <form method="post" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="field">
          <label for="email">{{ __('Email') }}</label>
          <input id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="username" required>
          @error('email')<span class="error" style="display:block">{{ $message }}</span>@enderror
        </div>
        <div class="field">
          <label for="password">{{ __('Contraseña nueva') }}</label>
          <x-password-input name="password" autocomplete="new-password" aria-describedby="password-requirements" />
          @error('password')<span class="error" style="display:block">{{ $message }}</span>@enderror
          <x-password-requirements id="password-requirements" />
        </div>
        <div class="field">
          <label for="password_confirmation">{{ __('Repetí la contraseña nueva') }}</label>
          <x-password-input name="password_confirmation" autocomplete="new-password" />
          <x-password-match />
        </div>
        <div class="wizard__actions"><span></span><button class="btn btn--secondary" type="submit">{{ __('Guardá la contraseña') }}</button></div>
      </form>
    </section>
  </main>
</x-layout>
