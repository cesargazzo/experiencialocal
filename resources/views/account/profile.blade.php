<x-layout title="Tu perfil" :noindex="true">
  <main class="container wizard" style="max-width:820px">
    <p class="eyebrow">Tu cuenta</p>
    <h1 class="title">Hola, <span class="hl">{{ Str::before($user->name, ' ') }}</span>.</h1>
    @include('account.partials.nav')

    <livewire:profile-photo />

    <section class="wizard__panel">
      <h2>Tus datos</h2>
      <form method="post" action="{{ route('cuenta.perfil.update') }}">
        @csrf
        @method('put')
        <div class="field">
          <label for="name">Nombre y apellido</label>
          <input id="name" @unless ($nameLocked) name="name" @endunless value="{{ old('name', $user->name) }}" autocomplete="name" @disabled($nameLocked) required>
          @if ($nameLocked)
            <span class="hint">Tu nombre quedó validado con tu documento, así que no se puede cambiar desde acá.</span>
          @endif
          @error('name')<span class="error" style="display:block">{{ $message }}</span>@enderror
        </div>
        <div class="field">
          <label for="birth_date">Fecha de nacimiento</label>
          <input id="birth_date" name="birth_date" type="date" value="{{ old('birth_date', $user->birth_date?->toDateString()) }}" min="1900-01-01" max="{{ now()->subYears(config('tinku.min_age'))->toDateString() }}" autocomplete="bday" @disabled($birthDateLocked) required>
          @if ($birthDateLocked)
            <span class="hint">Quedó validada con tu documento.</span>
          @elseif (! $user->birth_date)
            <span class="hint">Completala para seguir usando Tinku. No se muestra en tu perfil.</span>
          @endif
          @error('birth_date')<span class="error" style="display:block">{{ $message }}</span>@enderror
        </div>
        @php($selectedCountry = old('country_code', $user->country_code ?? 'AR'))
        <fieldset class="field-group" x-data="{ country: @js($selectedCountry) }" style="border:0;padding:0;margin:0">
          <legend class="hint" style="margin-bottom:8px">Dónde vivís. Lo podés cambiar cuando te mudes.</legend>
          <div class="grid-2">
            <div class="field">
              <label for="country_code">País</label>
              <select id="country_code" name="country_code" x-model="country" autocomplete="country" required>
                @foreach ($countries as $code => $countryName)
                  <option value="{{ $code }}" @selected($selectedCountry === $code)>{{ $countryName }}</option>
                @endforeach
              </select>
              @error('country_code')<span class="error" style="display:block">{{ $message }}</span>@enderror
            </div>
            @foreach ($provincesByCountry as $code => $provinces)
              <div class="field" x-show="country === @js($code)" @style(['display:none' => $selectedCountry !== $code])>
                <label for="province_id_{{ $code }}">Provincia</label>
                <select id="province_id_{{ $code }}" name="province_id" :disabled="country !== @js($code)" @disabled($selectedCountry !== $code) required>
                  <option value="">Elegí tu provincia</option>
                  @foreach ($provinces as $provinceId => $provinceName)
                    <option value="{{ $provinceId }}" @selected((int) old('province_id', $user->province_id) === $provinceId)>{{ $provinceName }}</option>
                  @endforeach
                </select>
                @error('province_id')<span class="error" style="display:block">{{ $message }}</span>@enderror
              </div>
            @endforeach
          </div>
          <div class="grid-2">
            <div class="field">
              <label for="city">Ciudad</label>
              <input id="city" name="city" value="{{ old('city', $user->city) }}" maxlength="80" autocomplete="address-level2" placeholder="Ej.: Chilecito" required>
              @error('city')<span class="error" style="display:block">{{ $message }}</span>@enderror
            </div>
            <div class="field">
              <label for="postal_code">Código postal <span class="hint">(opcional)</span></label>
              <input id="postal_code" name="postal_code" value="{{ old('postal_code', $user->postal_code) }}" maxlength="12" autocomplete="postal-code" placeholder="Ej.: 5360" style="text-transform:uppercase">
              @error('postal_code')<span class="error" style="display:block">{{ $message }}</span>@enderror
            </div>
          </div>
        </fieldset>
        <div class="grid-2">
          <div class="field">
            <label for="email">Email</label>
            <input id="email" value="{{ $user->email }}" disabled>
          </div>
          <div class="field">
            <label for="phone">Teléfono</label>
            <input id="phone" value="{{ $user->phone }}" disabled>
          </div>
        </div>
        <p class="hint" style="margin-top:12px">El email y el teléfono están validados. Para cambiarlos, escribinos y lo hacemos verificando tu identidad.</p>
        <div class="wizard__actions"><span></span><button class="btn btn--secondary" type="submit">Guardá tus datos</button></div>
      </form>
    </section>
  </main>
</x-layout>
