<x-layout title="Seguridad" description="Cómo cuidamos la identidad, los datos y los encuentros en Tinku.">
  <main class="container wizard" style="max-width:820px">
    <p class="eyebrow">Tinku</p>
    <h1 class="title">Encuentros <span class="hl">seguros</span>.</h1>
    <p class="lead">En Tinku la gente se encuentra en persona. Por eso la identidad es lo primero.</p>

    <section class="wizard__panel">
      <h2>Sabés con quién te encontrás</h2>
      <ul class="menu-list">
        <li><strong>Contacto confirmado</strong><span>Email (y teléfono) confirmados con un código.</span></li>
        <li><strong>Documento validado</strong><span>Documento de identidad y selfie con prueba de vida. Lo exigimos para reservar y para ofrecer experiencias.</span></li>
        <li><strong>Identidad y domicilio validados</strong><span>Además, el domicilio, revisado por el equipo de Tinku.</span></li>
      </ul>
      <p class="hint">Cada perfil muestra su nivel. Un mismo documento no puede estar en dos cuentas.</p>
    </section>

    <section class="wizard__panel">
      <h2>Cuidamos tus datos</h2>
      <ul>
        <li>No guardamos el número de tu documento: solo una huella que no permite reconstruirlo.</li>
        <li>Tu apellido lo ven solo personas con la identidad validada; el resto ve tu nombre.</li>
        <li>La dirección exacta de un encuentro la ve solo quien tiene la reserva confirmada.</li>
        <li>Los mensajes son privados y se guardan cifrados.</li>
        <li>Tu alimentación y tus alergias no quedan en ningún registro interno.</li>
        <li>Cada vez que alguien del equipo mira datos de una cuenta queda registrado.</li>
        <li>Podés <a href="{{ route('cuenta.seguridad') }}#tus-datos">descargar tus datos o eliminar tu cuenta</a> cuando quieras.</li>
      </ul>
    </section>

    <section class="wizard__panel">
      <h2>Consejos</h2>
      <ul>
        <li>Hablá y pagá siempre dentro de Tinku. Si alguien te pide arreglar por fuera, desconfiá.</li>
        <li>Activá el <a href="{{ route('cuenta.seguridad') }}#doble-factor">doble factor</a>.</li>
        <li>Contale a alguien de confianza a dónde vas y a qué hora.</li>
        <li>Si algo no te cierra, no vayas. Podés cancelar desde Tus reservas.</li>
      </ul>
    </section>

    <section class="wizard__panel">
      <h2>Si algo pasa</h2>
      <p>Denunciá cualquier mensaje desde la conversación con "Denunciar": lo revisa el equipo. Para cualquier otra situación escribinos a <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>.</p>
      <p><strong>Ante una emergencia, llamá al 911.</strong></p>
    </section>
  </main>
</x-layout>
