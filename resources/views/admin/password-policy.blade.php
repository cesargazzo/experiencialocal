<x-layout title="Política de contraseñas" :noindex="true">
  <main class="container wizard" style="max-width:820px">
    <p class="eyebrow">Administración</p>
    <h1 class="title">Política de <span class="hl">contraseñas</span>.</h1>
    @include('admin.partials.nav')
    <section class="wizard__panel">
      <form method="post" action="{{ route('admin.contrasenas.update') }}">
        @csrf
        @method('put')
        <h2>Requisitos</h2>
        <p>Aplican a las contraseñas nuevas y a los cambios. Las contraseñas actuales siguen valiendo.</p>
        <div class="field">
          <label for="min_length">Largo mínimo</label>
          <input id="min_length" name="min_length" type="number" min="{{ \App\Support\PasswordPolicy::ABSOLUTE_MIN_LENGTH }}" max="64" value="{{ old('min_length', $policy->minLength) }}" required>
          <span class="hint">Nunca menos de {{ \App\Support\PasswordPolicy::ABSOLUTE_MIN_LENGTH }} caracteres.</span>
          @error('min_length')<span class="error" style="display:block">{{ $message }}</span>@enderror
        </div>
        <div style="margin-top:12px">
          @foreach ([
            'require_mixed_case' => ['Mayúsculas y minúsculas', 'Por lo menos una de cada una.'],
            'require_numbers' => ['Números', 'Por lo menos un número.'],
            'require_symbols' => ['Símbolos', 'Por lo menos un símbolo, como ! o #.'],
            'check_uncompromised' => ['Contraseñas filtradas', 'Rechaza contraseñas que aparecen en filtraciones públicas. Consulta el servicio Have I Been Pwned sin enviar la contraseña.'],
          ] as $flag => [$label, $help])
            <label class="toggle-row">
              <input type="checkbox" name="{{ $flag }}" value="1" @checked(old($flag, $policy->toArray()[$flag]))>
              <span><strong>{{ $label }}</strong><small>{{ $help }}</small></span>
            </label>
          @endforeach
        </div>

        <h2 style="margin-top:32px">Intentos de ingreso</h2>
        <p>Después de varios intentos fallidos, la cuenta queda bloqueada un rato desde esa conexión.</p>
        <div class="grid-2">
          <div class="field">
            <label for="max_login_attempts">Intentos permitidos</label>
            <input id="max_login_attempts" name="max_login_attempts" type="number" min="3" max="20" value="{{ old('max_login_attempts', $policy->maxLoginAttempts) }}" required>
            @error('max_login_attempts')<span class="error" style="display:block">{{ $message }}</span>@enderror
          </div>
          <div class="field">
            <label for="lockout_minutes">Minutos de bloqueo</label>
            <input id="lockout_minutes" name="lockout_minutes" type="number" min="1" max="1440" value="{{ old('lockout_minutes', $policy->lockoutMinutes) }}" required>
            @error('lockout_minutes')<span class="error" style="display:block">{{ $message }}</span>@enderror
          </div>
        </div>
        <div class="wizard__actions"><span></span><button class="btn btn--secondary" type="submit">Guardá los cambios</button></div>
      </form>
    </section>
  </main>
</x-layout>
