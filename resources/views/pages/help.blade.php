<x-layout title="Ayuda" description="Preguntas frecuentes sobre cómo reservar y cómo recibir gente en Tinku.">
  <main class="container wizard" style="max-width:820px">
    <p class="eyebrow">Tinku</p>
    <h1 class="title">¿En qué te <span class="hl">ayudamos</span>?</h1>

    <section class="wizard__panel faq">
      <h2>Si querés reservar</h2>
      <details><summary>¿Cómo reservo una experiencia?</summary><p>Elegí la experiencia, la fecha y cuántas personas van, y pedí tu lugar. El anfitrión confirma dentro de las 24 horas. Vas a ver el estado en <a href="{{ route('cuenta.reservas') }}">Tus reservas</a> y te avisamos en la campanita y por mail.</p></details>
      <details><summary>¿Por qué tengo que validar mi identidad?</summary><p>Porque vas a entrar a la casa de alguien, o alguien va a entrar a la tuya. Para reservar pedimos tu documento y una selfie con prueba de vida. Así las dos partes saben con quién se encuentran. Mirá <a href="{{ route('seguridad') }}">cómo cuidamos tu seguridad</a>.</p></details>
      <details><summary>¿Puedo preguntarle algo al anfitrión antes de reservar?</summary><p>Sí. En cada experiencia, en "Quién te recibe", tocá "Preguntale a…". Los mensajes son privados y hasta que haya una reserva confirmada ocultamos teléfonos, mails y enlaces.</p></details>
      <details><summary>¿Dónde es exactamente?</summary><p>En la experiencia ves la zona aproximada. La dirección exacta y cómo llegar aparecen en Tus reservas cuando el anfitrión confirma, y te la recordamos el día anterior.</p></details>
      <details><summary>Tengo una alergia o una dieta especial</summary><p>Cargala en <a href="{{ route('cuenta.perfil') }}#alimentacion">tu perfil</a>. Al reservar te mostramos qué cubre cada experiencia y se la pasamos al anfitrión con tu reserva.</p></details>
      <details><summary>¿Cómo cancelo?</summary><p>Desde Tus reservas, con "Cancelá la reserva". Le avisamos al anfitrión y se liberan los lugares. Antes de confirmar te mostramos cuánto se te devuelve.</p></details>
      <details><summary>¿Cuánto se devuelve si cancelo?</summary><p>Depende de la política que eligió el anfitrión, que figura en cada experiencia y queda fija en tu reserva: <strong>flexible</strong> (sin costo hasta 24 horas antes), <strong>moderada</strong> (sin costo hasta 3 días antes y el 50% hasta 24 horas antes) o <strong>estricta</strong> (sin costo hasta 7 días antes y el 50% hasta 3 días antes). Si todavía no te confirmaron, no se cobró nada. Si cancela el anfitrión, se devuelve todo. Si no te presentás, no hay devolución.</p></details>
      <details><summary>¿Cómo dejo una opinión?</summary><p>Después de la experiencia te llega "¿Cómo te fue?". Opinan solo quienes fueron, una vez por reserva.</p></details>
    </section>

    <section class="wizard__panel faq">
      <h2>Si querés recibir gente</h2>
      <details><summary>¿Qué necesito para ser anfitrión?</summary><p>Validar tu documento. Con eso cargás tu experiencia y la revisamos antes de publicarla. Para publicar sin restricciones también validamos tu domicilio.</p></details>
      <details><summary>¿Por qué mi experiencia está "en revisión"?</summary><p>Revisamos cada experiencia nueva y cada cambio de texto o foto, para cuidar a todos. Te avisamos cuando está aprobada. Mientras tanto la podés editar desde <a href="{{ route('anfitrion.panel') }}">tu espacio de anfitrión</a>.</p></details>
      <details><summary>¿Cómo cargo fechas?</summary><p>En tu experiencia, sección Fechas: una suelta o varias de una vez, eligiendo los días de la semana y un rango.</p></details>
      <details><summary>¿Cuánto cobra Tinku?</summary><p>Depende de tu plan: mirá los <a href="{{ route('home') }}#planes">planes</a>. En el alta te mostramos cuánto recibís por persona.</p></details>
    </section>

    <section class="wizard__panel faq">
      <h2>Tu cuenta</h2>
      <details><summary>Me olvidé la contraseña</summary><p>Pedí una nueva desde <a href="{{ route('password.request') }}">¿Te olvidaste la contraseña?</a>.</p></details>
      <details><summary>¿Cómo protejo mi cuenta?</summary><p>Activá el doble factor en <a href="{{ route('cuenta.seguridad') }}#doble-factor">Seguridad</a>: además de la contraseña te pedimos un código de una app del celular.</p></details>
      <details><summary>Quiero mis datos o eliminar mi cuenta</summary><p>Desde <a href="{{ route('cuenta.seguridad') }}#tus-datos">Seguridad → Tus datos personales</a> podés descargar todo lo que guardamos de vos o eliminar tu cuenta.</p></details>
    </section>

    <section class="wizard__panel">
      <h2>¿No encontraste la respuesta?</h2>
      <p>Escribinos a <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a> y te respondemos.</p>
    </section>
  </main>
</x-layout>
