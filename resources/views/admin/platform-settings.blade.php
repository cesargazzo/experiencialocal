<x-layout title="Configuración" :noindex="true">
  <main class="container wizard" style="max-width:820px">
    <p class="eyebrow">Administración</p>
    <h1 class="title">Configuración.</h1>
    @include('admin.partials.nav')
    <section class="wizard__panel">
      <form method="post" action="{{ route('admin.configuracion.update') }}">
        @csrf
        @method('put')
        <h2>Verificación del teléfono</h2>
        <label class="toggle-row">
          <input type="checkbox" name="sms_verification" value="1" @checked($settings->smsVerification)>
          <span>
            <strong>Pedir un código por SMS para confirmar el teléfono</strong>
            <small>Apagado: el nivel 1 pide solo el email confirmado y el teléfono queda como dato de contacto. Encendelo cuando haya un proveedor de SMS configurado.</small>
          </span>
        </label>
        @if (! $settings->smsVerification && $withoutConfirmedPhone > 0)
          <p class="notice" style="margin-top:16px">Si lo encendés, {{ $withoutConfirmedPhone }} {{ $withoutConfirmedPhone === 1 ? 'cuenta baja' : 'cuentas bajan' }} al nivel 0 hasta confirmar su teléfono.</p>
        @endif
        <div class="wizard__actions"><span></span><button class="btn btn--secondary" type="submit">Guardá los cambios</button></div>
      </form>
    </section>
  </main>
</x-layout>
