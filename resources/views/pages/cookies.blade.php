@php
  $sessionMinutes = config('session.lifetime');
  $sessionDuration = config('session.expire_on_close') ? 'hasta que cerrás el navegador' : plural_es(intdiv($sessionMinutes, 60) ?: $sessionMinutes, intdiv($sessionMinutes, 60) ? 'hora' : 'minuto', intdiv($sessionMinutes, 60) ? 'horas' : 'minutos').' sin actividad';
@endphp
<x-layout title="Política de cookies" description="Qué cookies usa Tinku, para qué sirven y cómo elegir cuáles aceptás.">
  <main class="container wizard" style="max-width:820px">
    <p class="eyebrow">Tinku</p>
    <h1 class="title">Política de <span class="hl">cookies</span>.</h1>

    @if (session('status'))<p class="notice notice--ok" role="status">{{ session('status') }}</p>@endif

    <section class="wizard__panel">
      <h2>En pocas palabras</h2>
      <p>Las cookies son pequeños archivos que un sitio guarda en tu navegador. En Tinku usamos <strong>las necesarias para que el sitio funcione</strong> (tu sesión, la seguridad de los formularios y tu elección sobre cookies) y, <strong>solo si nos das permiso</strong>, las de Google Analytics para entender cómo se usa el sitio y mejorarlo.</p>
      <p>No usamos cookies de publicidad, no armamos perfiles para mostrarte anuncios y no vendemos tus datos.</p>
    </section>

    <section class="wizard__panel" id="preferencias">
      <h2>Tu elección</h2>
      @if ($analyticsEnabled)
        <p>
          @if ($consent === null)
            Todavía no elegiste. Mientras tanto, solo usamos las cookies necesarias.
          @elseif ($consent->analytics)
            Aceptaste las cookies de análisis.
          @else
            Elegiste usar solo las cookies necesarias.
          @endif
          Podés cambiarlo cuando quieras.
        </p>
        <form method="post" action="{{ route('cookies.guardar') }}">
          @csrf
          <div style="display:grid;gap:8px">
            <label class="choice" style="align-items:flex-start"><input type="checkbox" checked disabled style="margin-top:1px"> <span style="display:block"><strong>Necesarias</strong> (siempre activas)<br><small style="color:var(--tinta-suave)">Mantienen tu sesión, protegen los formularios y recuerdan esta elección. Sin ellas Tinku no funciona.</small></span></label>
            <input type="hidden" name="analytics" value="0">
            <label class="choice" style="align-items:flex-start"><input type="checkbox" name="analytics" value="1" @checked($consent?->analytics) style="margin-top:1px"> <span style="display:block"><strong>De análisis</strong> (Google Analytics)<br><small style="color:var(--tinta-suave)">Cuentan visitas y páginas vistas de forma agregada para saber qué funciona y qué mejorar. Desactivamos las señales de publicidad de Google.</small></span></label>
          </div>
          <div class="wizard__actions"><span></span><button class="btn btn--secondary" type="submit">Guardá tu elección</button></div>
        </form>
      @else
        <p>Por ahora Tinku usa solo cookies necesarias, así que no hace falta que elijas nada. Si sumamos otras, te lo vamos a preguntar antes.</p>
      @endif
    </section>

    <section class="wizard__panel">
      <h2>Qué cookies usamos</h2>
      <div style="overflow-x:auto">
        <table class="cookie-table">
          <thead><tr><th>Nombre</th><th>De quién</th><th>Para qué</th><th>Duración</th></tr></thead>
          <tbody>
            <tr><td><code>{{ config('session.cookie') }}</code></td><td>Tinku</td><td>Necesaria. Mantiene tu sesión abierta mientras navegás y recuerda tu idioma.</td><td>{{ $sessionDuration }}</td></tr>
            <tr><td><code>XSRF-TOKEN</code></td><td>Tinku</td><td>Necesaria. Evita que otro sitio envíe formularios en tu nombre.</td><td>{{ $sessionDuration }}</td></tr>
            <tr><td><code>remember_web_…</code></td><td>Tinku</td><td>Necesaria, solo si tildás "Recordarme" al ingresar. Te mantiene adentro en este navegador.</td><td>Hasta 400 días, o hasta que salgas de tu cuenta</td></tr>
            <tr><td><code>{{ \App\Support\CookieConsent::COOKIE }}</code></td><td>Tinku</td><td>Necesaria. Guarda tu elección sobre las cookies.</td><td>{{ \App\Support\CookieConsent::DAYS / 365 === 1 ? '12 meses' : \App\Support\CookieConsent::DAYS.' días' }}</td></tr>
            @if ($analyticsEnabled)
              <tr><td><code>_ga</code>, <code>_ga_…</code></td><td>Google Analytics</td><td>De análisis, solo con tu permiso. Distinguen visitas de forma anónima para medir el uso del sitio.</td><td>2 años</td></tr>
            @endif
          </tbody>
        </table>
      </div>
      <p class="hint" style="margin-top:12px">Además guardamos en tu navegador (almacenamiento local, no es una cookie) si ya cerraste el saludo de cumpleaños, para no mostrártelo de nuevo.</p>
    </section>

    <section class="wizard__panel" id="terceros">
      <h2>Con quién trabajamos</h2>
      <p>Estos servicios se cargan desde sus propios servidores, que reciben tu dirección IP y los datos técnicos de tu navegador:</p>
      <ul>
        @if ($analyticsEnabled)
          <li><strong>Google Analytics</strong> (Google LLC): estadísticas de uso, solo si las aceptás. <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">Política de privacidad de Google</a> · <a href="https://tools.google.com/dlpage/gaoptout" target="_blank" rel="noopener">complemento para desactivarlo</a>.</li>
        @endif
        <li><strong>Google Fonts</strong> (Google LLC): las tipografías del sitio. No deja cookies.</li>
        <li><strong>Instituto Geográfico Nacional</strong>: los mapas de las experiencias. No deja cookies.</li>
      </ul>
      <p>No compartimos con ellos tus datos de cuenta, reservas ni mensajes.</p>
    </section>

    <section class="wizard__panel">
      <h2>Cómo borrarlas o bloquearlas</h2>
      <p>Además de elegir acá, podés borrar o bloquear cookies desde la configuración de tu navegador. Si bloqueás las necesarias no vas a poder ingresar a tu cuenta.</p>
      <p>Si tenés dudas, escribinos a <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>. Más sobre cómo cuidamos tus datos en los <a href="{{ route('terminos') }}">términos y condiciones</a>.</p>
      <p class="hint">Última actualización: {{ \Illuminate\Support\Carbon::parse('2026-10-02')->isoFormat('LL') }}.</p>
    </section>
  </main>
</x-layout>
