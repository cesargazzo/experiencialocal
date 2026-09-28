<x-layout title="Código de seguridad" :noindex="true">
  <main class="container wizard" style="max-width:520px">
    <p class="eyebrow">Tu cuenta</p>
    <h1 class="title">Un paso <span class="hl">más</span>.</h1>
    <section class="wizard__panel" x-data="{ recovery: false }">
      <p x-show="! recovery">Abrí tu app de autenticación y escribí el código de 6 dígitos de Tinku.</p>
      <p x-show="recovery" x-cloak>Escribí uno de tus códigos de recuperación. Cada uno sirve una sola vez.</p>
      <form method="post" action="{{ $action ?? route('login.2fa') }}">
        @csrf
        <div class="field">
          <label for="code" x-text="recovery ? 'Código de recuperación' : 'Código de la app'">Código de la app</label>
          <input id="code" name="code" x-bind:inputmode="recovery ? 'text' : 'numeric'" inputmode="numeric" autocomplete="one-time-code" maxlength="20" required autofocus class="code-input">
          @error('code')<span class="error" style="display:block">{{ $message }}</span>@enderror
        </div>
        <div class="wizard__actions">
          <button type="button" class="btn btn--ghost" x-on:click="recovery = ! recovery; $nextTick(() => document.getElementById('code').focus())" x-text="recovery ? 'Usar la app' : 'No tengo el celular'">No tengo el celular</button>
          <button class="btn btn--secondary" type="submit">{{ $submitLabel ?? 'Ingresá' }}</button>
        </div>
      </form>
    </section>
  </main>
</x-layout>
