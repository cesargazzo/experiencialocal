<x-layout :title="__('Ingresar')" :noindex="true">
  <main class="container wizard" style="max-width:520px">
    <p class="eyebrow">{{ __('Tu cuenta') }}</p>
    <h1 class="title">{!! __('Hola de <span class="hl">nuevo</span>.') !!}</h1>
    <section class="wizard__panel">
      <form method="post" action="{{ route('login') }}">
        @csrf
        <div class="field"><label for="email">{{ __('Email') }}</label><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>@error('email')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
        <div class="field"><label for="password">{{ __('Contraseña') }}</label><x-password-input name="password" autocomplete="current-password" /><a class="link" href="{{ route('password.request') }}">{{ __('¿Te olvidaste la contraseña?') }}</a></div>
        <label style="display:flex;gap:8px;align-items:center;margin-top:14px;font-size:14px"><input type="checkbox" name="remember"> {{ __('Recordarme') }}</label>
        <div class="wizard__actions"><a class="btn btn--ghost" href="{{ route('register') }}">{{ __('Crear cuenta') }}</a><button class="btn btn--secondary" type="submit">{{ __('Ingresá') }}</button></div>
      </form>
    </section>
  </main>
</x-layout>
