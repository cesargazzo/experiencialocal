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
        @unless ($nameLocked && $birthDateLocked)
          <div class="wizard__actions"><span></span><button class="btn btn--secondary" type="submit">Guardá tus datos</button></div>
        @endunless
      </form>
    </section>
  </main>
</x-layout>
