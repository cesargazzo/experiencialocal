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

    @unless ($forced)
      @php($user = auth()->user())
      @if (\App\Models\User::twoFactorAvailable())
      <section class="wizard__panel" id="doble-factor">
        <h2>Doble factor</h2>
        <p>Además de la contraseña, al ingresar te pedimos un código de 6 dígitos que genera una app en tu celular (Google Authenticator, Microsoft Authenticator, 1Password, Authy…). Si alguien consigue tu contraseña, igual no puede entrar.
          @if ($user->isAdmin())<strong>Para la administración es obligatorio.</strong>@endif
        </p>

        @if ($codes = session('recovery_codes'))
          <div class="notice recovery-codes" role="status">
            <p style="margin:0 0 8px"><strong>Guardá estos códigos de recuperación.</strong> Sirven para entrar si perdés el celular; cada uno se usa una vez. No los vamos a volver a mostrar.</p>
            <ul>@foreach ($codes as $code)<li><code>{{ $code }}</code></li>@endforeach</ul>
          </div>
        @endif

        @if ($user->hasTwoFactor())
          <p class="badge badge--ok" style="display:inline-flex">Activado desde el {{ $user->two_factor_confirmed_at->timezone(config('tinku.timezone'))->format('d/m/Y') }}</p>
          <p class="hint">Te quedan {{ count($user->two_factor_recovery_codes ?? []) }} códigos de recuperación.</p>
          <div class="grid-2">
            <form method="post" action="{{ route('cuenta.2fa.codes') }}" class="subform">
              @csrf
              <h3>Códigos de recuperación nuevos</h3>
              <div class="field"><label for="codes_password">Tu contraseña</label><x-password-input name="password" id="codes_password" autocomplete="current-password" /></div>
              <button class="btn btn--tertiary btn--sm" type="submit" style="margin-top:12px">Generá códigos nuevos</button>
            </form>
            <form method="post" action="{{ route('cuenta.2fa.destroy') }}" class="subform">
              @csrf
              @method('delete')
              <h3>Desactivar</h3>
              <div class="field"><label for="off_password">Tu contraseña</label><x-password-input name="password" id="off_password" autocomplete="current-password" /></div>
              <div class="field"><label for="off_code">Código de la app</label><input id="off_code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="20" required></div>
              @error('code', 'twoFactor')<span class="error" style="display:block">{{ $message }}</span>@enderror
              <button class="btn btn--ghost btn--sm" type="submit" style="margin-top:12px">Desactivá el doble factor</button>
            </form>
          </div>
          @error('password', 'twoFactor')<span class="error" style="display:block">{{ $message }}</span>@enderror
        @elseif ($user->two_factor_secret)
          @php($uri = \App\Support\Totp::provisioningUri($user->two_factor_secret, $user->email, config('app.name')))
          <p style="margin:12px 0 0"><strong>1.</strong> Abrí tu app de autenticación y escaneá este código.</p>
          <div class="qr">{!! \App\Support\Totp::qrSvg($uri) !!}</div>
          <p class="hint">Si no podés escanearlo, cargá esta clave a mano: <code class="secret">{{ trim(chunk_split($user->two_factor_secret, 4, ' ')) }}</code></p>
          <form method="post" action="{{ route('cuenta.2fa.confirm') }}">
            @csrf
            <div class="field" style="max-width:260px">
              <label for="code"><strong>2.</strong> Escribí el código que muestra la app</label>
              <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required class="code-input" autofocus>
              @error('code', 'twoFactor')<span class="error" style="display:block">{{ $message }}</span>@enderror
            </div>
            <div class="wizard__actions"><span></span><button class="btn btn--primary" type="submit">Activá el doble factor</button></div>
          </form>
        @else
          <form method="post" action="{{ route('cuenta.2fa.start') }}">
            @csrf
            <div class="field" style="max-width:360px"><label for="tf_password">Confirmá tu contraseña para empezar</label><x-password-input name="password" id="tf_password" autocomplete="current-password" /></div>
            @error('password', 'twoFactor')<span class="error" style="display:block">{{ $message }}</span>@enderror
            <div class="wizard__actions"><span></span><button class="btn btn--primary" type="submit">Configurá el doble factor</button></div>
          </form>
        @endif
      </section>
      @endif

      <section class="wizard__panel" id="tus-datos">
        <h2>Tus datos personales</h2>
        <p>Podés descargar todo lo que Tinku guarda de vos: tu perfil, reservas, opiniones, mensajes, términos aceptados y accesos recientes.</p>
        <a class="btn btn--tertiary btn--sm" href="{{ route('cuenta.datos.descargar') }}"><x-icon name="copy" :size="16" /> Descargá tus datos</a>

        <details class="danger-zone" @if ($errors->deletion->any()) open @endif>
          <summary>Eliminar tu cuenta</summary>
          <p>Borramos tus datos personales: nombre, contacto, documento, fotos, domicilio, intereses y avisos. Tus reservas pasadas, opiniones y mensajes quedan sin tu nombre. No se puede deshacer.</p>
          <form method="post" action="{{ route('cuenta.eliminar') }}">
            @csrf
            @method('delete')
            <div class="field" style="max-width:360px"><label for="del_password">Tu contraseña</label><x-password-input name="password" id="del_password" autocomplete="current-password" /></div>
            @if ($user->hasTwoFactor())
              <div class="field" style="max-width:360px"><label for="del_code">Código de tu app</label><input id="del_code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="20" required></div>
            @endif
            <label class="toggle-row"><input type="checkbox" name="confirm" value="1" required><span>Entiendo que se eliminan mis datos y no se puede deshacer.</span></label>
            @foreach ($errors->deletion->all() as $problem)<span class="error" style="display:block">{{ $problem }}</span>@endforeach
            <div class="wizard__actions"><span></span><button class="btn btn--danger" type="submit">Eliminá mi cuenta</button></div>
          </form>
        </details>
      </section>
    @endunless
  </main>
</x-layout>
