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
        <div class="grid-2">
          <div class="field">
            <label for="first_name">Nombre</label>
            <input id="first_name" @unless ($nameLocked) name="first_name" @endunless value="{{ old('first_name', $user->first_name) }}" autocomplete="given-name" maxlength="60" @disabled($nameLocked) required>
            @error('first_name')<span class="error" style="display:block">{{ $message }}</span>@enderror
          </div>
          <div class="field">
            <label for="last_name">Apellido</label>
            <input id="last_name" @unless ($nameLocked) name="last_name" @endunless value="{{ old('last_name', $user->last_name) }}" autocomplete="family-name" maxlength="80" @disabled($nameLocked) required>
            @error('last_name')<span class="error" style="display:block">{{ $message }}</span>@enderror
          </div>
        </div>
        <span class="hint" style="display:block;margin:8px 0 0">
          {{ $nameLocked ? 'Tu nombre quedó validado con tu documento, así que no se puede cambiar desde acá.' : 'Como figuran en tu documento.' }}
          Tu apellido solo lo ven personas con la identidad validada; el resto ve solo tu nombre.
        </span>
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
        <fieldset class="field-group" x-data="{ country: @js($selectedCountry) }">
          <legend>Dónde vivís</legend>
          <span class="hint">Lo podés cambiar cuando te mudes.</span>
          <div class="grid-2">
            <div class="field">
              <label for="country_code">País de residencia</label>
              <select id="country_code" name="country_code" x-model="country" autocomplete="country" required>
                <x-country-options :selected="$selectedCountry" />
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
        <fieldset class="field-group">
          <legend>Contacto</legend>
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
        <p class="hint" style="margin:12px 0 0">El email y el teléfono están validados. Para cambiarlos, escribinos y lo hacemos verificando tu identidad.</p>
        </fieldset>
        <div class="wizard__actions"><span></span><button class="btn btn--secondary" type="submit">Guardá tus datos</button></div>
      </form>
    </section>

    <section class="wizard__panel" id="alimentacion">
      <h2>Tu alimentación</h2>
      <p>Marcá lo que necesitás y te avisamos en cada experiencia si el anfitrión lo cubre. Es opcional y solo lo ve el anfitrión de las experiencias que reserves.</p>
      <form method="post" action="{{ route('cuenta.alimentacion.update') }}">
        @csrf
        @method('put')
        @php($selectedNeeds = old('dietary_needs', $user->dietary_needs?->map->value->all() ?? []))
        <div class="choice-grid">
          @foreach ($dietaryOptions as $option)
            <label class="choice">
              <input type="checkbox" name="dietary_needs[]" value="{{ $option->value }}" @checked(in_array($option->value, $selectedNeeds, true))>
              <span>{{ $option->needLabel() }}</span>
            </label>
          @endforeach
        </div>
        @error('dietary_needs.*')<span class="error" style="display:block">{{ $message }}</span>@enderror
        <div class="field" style="margin-top:16px">
          <label for="food_allergies">Alergias o intolerancias</label>
          <input id="food_allergies" name="food_allergies" value="{{ old('food_allergies', $user->food_allergies) }}" maxlength="300" placeholder="Ej.: maní, mariscos, frutos secos">
          @error('food_allergies')<span class="error" style="display:block">{{ $message }}</span>@enderror
        </div>
        <div class="wizard__actions"><span></span><button class="btn btn--secondary" type="submit">Guardá tu alimentación</button></div>
      </form>
    </section>
  </main>
</x-layout>
