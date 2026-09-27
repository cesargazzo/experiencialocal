<x-layout title="Creá tu cuenta" :noindex="true">
  <main class="container wizard" style="max-width:640px">
    <p class="eyebrow">Tu cuenta</p>
    <h1 class="title">Creá tu <span class="hl">cuenta</span>.</h1>
    <section class="wizard__panel">
      @if ($invitation)
        <p class="notice"><strong>{{ $invitation->inviter->first_name }}</strong> te invitó a Tinku.</p>
      @endif
      <p>Una sola cuenta sirve para reservar y para ser anfitrión. Podés venir de cualquier país.</p>
      <form method="post" action="{{ route('register') }}">
        @csrf
        @php($invitedName = preg_split('/\s+/u', trim($invitation?->name ?? ''), 2) + ['', ''])
        <div class="grid-2">
          <div class="field"><label for="first_name">Nombre</label><input id="first_name" name="first_name" value="{{ old('first_name', $invitedName[0]) }}" autocomplete="given-name" maxlength="60" required>@error('first_name')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
          <div class="field"><label for="last_name">Apellido</label><input id="last_name" name="last_name" value="{{ old('last_name', $invitedName[1]) }}" autocomplete="family-name" maxlength="80" required>@error('last_name')<span class="error" style="display:block">{{ $message }}</span>@enderror<span class="hint">Como figuran en tu documento. En Tinku el apellido solo lo ven personas con la identidad validada.</span></div>
          <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email', $invitation?->email) }}" autocomplete="email" required>@error('email')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
          <div class="field"><label for="phone">Teléfono</label><input id="phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="+54 380 …" autocomplete="tel" required>@error('phone')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
          <div class="field"><label for="birth_date">Fecha de nacimiento</label><input id="birth_date" name="birth_date" type="date" value="{{ old('birth_date') }}" min="1900-01-01" max="{{ now()->subYears(config('tinku.min_age'))->toDateString() }}" autocomplete="bday" required>@error('birth_date')<span class="error" style="display:block">{{ $message }}</span>@enderror<span class="hint">Tenés que tener al menos {{ config('tinku.min_age') }} años. No se muestra en tu perfil.</span></div>
          <div class="field"><label for="nationality_code">Nacionalidad</label>
            <select id="nationality_code" name="nationality_code" required>
              <x-country-options :selected="old('nationality_code', 'AR')" />
            </select>
            <span class="hint">Define cómo validamos tu documento. Argentinos por RENAPER; el resto por un proveedor internacional.</span>
          </div>
        </div>
        <hr class="form-divider">
        <div class="grid-2">
          <div class="field"><label for="password">Contraseña</label><x-password-input name="password" autocomplete="new-password" aria-describedby="password-requirements" />@error('password')<span class="error" style="display:block">{{ $message }}</span>@enderror<x-password-requirements id="password-requirements" /></div>
          <div class="field"><label for="password_confirmation">Repetí la contraseña</label><x-password-input name="password_confirmation" autocomplete="new-password" /><x-password-match /></div>
        </div>
        @if ($terms)
          <input type="hidden" name="terms_version_id" value="{{ $terms->id }}">
          <label class="toggle-row terms-accept">
            <input type="checkbox" name="accept_terms" value="1" @checked(old('accept_terms')) required>
            <span>Leí y acepto los <a href="{{ route('terminos') }}" target="_blank" rel="noopener">términos y condiciones</a> (versión {{ $terms->version }}).</span>
          </label>
          @error('accept_terms')<span class="error" style="display:block">{{ $message }}</span>@enderror
          @error('terms_version_id')<span class="error" style="display:block">{{ $message }}</span>@enderror
        @endif
        <div class="wizard__actions"><a class="btn btn--ghost" href="{{ route('login') }}">Ya tengo cuenta</a><button class="btn btn--secondary" type="submit">Creá tu cuenta <x-icon name="arrow-right" :size="18" class="icon--arrow" /></button></div>
      </form>
    </section>
  </main>
</x-layout>
