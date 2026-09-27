<x-layout title="Creá tu cuenta" :noindex="true">
  <main class="container wizard" style="max-width:640px">
    <p class="eyebrow">Tu cuenta</p>
    <h1 class="title">Creá tu <span class="hl">cuenta</span>.</h1>
    <section class="wizard__panel">
      <p>Una sola cuenta sirve para reservar y para ser anfitrión. Podés venir de cualquier país.</p>
      <form method="post" action="{{ route('register') }}">
        @csrf
        <div class="grid-2">
          <div class="field"><label for="name">Nombre y apellido</label><input id="name" name="name" value="{{ old('name') }}" autocomplete="name" required>@error('name')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
          <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>@error('email')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
          <div class="field"><label for="phone">Teléfono</label><input id="phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="+54 380 …" autocomplete="tel" required>@error('phone')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
          <div class="field"><label for="nationality_code">Nacionalidad</label>
            <select id="nationality_code" name="nationality_code" required>
              @foreach (['AR' => 'Argentina', 'BO' => 'Bolivia', 'BR' => 'Brasil', 'CL' => 'Chile', 'CO' => 'Colombia', 'ES' => 'España', 'US' => 'Estados Unidos', 'FR' => 'Francia', 'DE' => 'Alemania', 'IT' => 'Italia', 'MX' => 'México', 'PY' => 'Paraguay', 'PE' => 'Perú', 'GB' => 'Reino Unido', 'UY' => 'Uruguay', 'XX' => 'Otro'] as $code => $name)
                <option value="{{ $code }}" @selected(old('nationality_code', 'AR') === $code)>{{ $name }}</option>
              @endforeach
            </select>
            <span class="hint">Define cómo validamos tu documento. Argentinos por RENAPER; el resto por un proveedor internacional.</span>
          </div>
          <div class="field"><label for="password">Contraseña</label><x-password-input name="password" autocomplete="new-password" aria-describedby="password-requirements" />@error('password')<span class="error" style="display:block">{{ $message }}</span>@enderror<x-password-requirements id="password-requirements" /></div>
          <div class="field"><label for="password_confirmation">Repetí la contraseña</label><x-password-input name="password_confirmation" autocomplete="new-password" /><x-password-match /></div>
        </div>
        <div class="wizard__actions"><a class="btn btn--ghost" href="{{ route('login') }}">Ya tengo cuenta</a><button class="btn btn--secondary" type="submit">Creá tu cuenta <x-icon name="arrow-right" :size="18" class="icon--arrow" /></button></div>
      </form>
    </section>
  </main>
</x-layout>
