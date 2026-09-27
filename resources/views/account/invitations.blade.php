<x-layout title="Invitaciones" :noindex="true">
  <main class="container wizard" style="max-width:820px">
    <p class="eyebrow">Tu cuenta</p>
    <h1 class="title">Invitá a <span class="hl">alguien</span>.</h1>
    @include('account.partials.nav')

    @if ($link = session('invitation_link'))
      @php
        $message = ($link['name'] ? 'Hola, '.Str::before($link['name'], ' ').'. ' : 'Hola. ').'Te invito a Tinku: reservás experiencias con la gente que vive en cada lugar. Sumate acá: '.$link['url'];
      @endphp
      <section class="wizard__panel invite-link" x-data="{ copied: false }">
        <h2>Tu enlace está listo</h2>
        <p>Sirve una sola vez y vence el {{ $link['expires'] }}. Guardalo ahora: por seguridad no lo volvemos a mostrar.</p>
        <div class="invite-link__row">
          <input id="invitation-url" class="inline-input" value="{{ $link['url'] }}" readonly aria-label="Enlace de invitación" x-on:focus="$el.select()">
          <button type="button" class="btn btn--tertiary btn--sm" x-on:click="navigator.clipboard.writeText(@js($link['url'])).then(() => { copied = true; setTimeout(() => copied = false, 2500) })">
            <x-icon name="copy" :size="16" /> <span x-text="copied ? 'Copiado' : 'Copiá'">Copiá</span>
          </button>
        </div>
        <a class="btn btn--whatsapp" href="https://wa.me/?text={{ rawurlencode($message) }}" target="_blank" rel="noopener"><x-icon name="whatsapp-logo" :size="20" /> Compartilo por WhatsApp</a>
      </section>
    @endif

    <section class="wizard__panel">
      <h2><x-icon name="link" :size="22" /> Con un enlace</h2>
      <p>Generá un enlace y mandalo por WhatsApp o por donde quieras.</p>
      <form method="post" action="{{ route('cuenta.invitaciones.enlace') }}">
        @csrf
        <div class="field">
          <label for="link_name">Nombre de quien invitás (opcional)</label>
          <input id="link_name" name="name" maxlength="120" placeholder="Para saludarla en el mensaje">
          @error('name', 'link')<span class="error" style="display:block">{{ $message }}</span>@enderror
        </div>
        <div class="wizard__actions"><span class="hint">Te quedan {{ $remainingToday }} invitaciones hoy.</span><button class="btn btn--secondary" type="submit" @disabled($remainingToday === 0)>Generá el enlace</button></div>
      </form>
    </section>

    <section class="wizard__panel">
      <h2><x-icon name="envelope-simple" :size="22" /> Por email</h2>
      <p>Le mandamos un mail con tu nombre y el enlace para sumarse.</p>
      <form method="post" action="{{ route('cuenta.invitaciones.email') }}">
        @csrf
        <div class="grid-2">
          <div class="field">
            <label for="invite_name">Nombre</label>
            <input id="invite_name" name="name" value="{{ old('name') }}" maxlength="120" required>
            @error('name')<span class="error" style="display:block">{{ $message }}</span>@enderror
          </div>
          <div class="field">
            <label for="invite_email">Email</label>
            <input id="invite_email" name="email" type="email" value="{{ old('email') }}" required>
            @error('email')<span class="error" style="display:block">{{ $message }}</span>@enderror
          </div>
        </div>
        <div class="wizard__actions"><span></span><button class="btn btn--secondary" type="submit" @disabled($remainingToday === 0)>Mandá la invitación</button></div>
      </form>
    </section>

    @if ($invitations->isNotEmpty())
      <section class="wizard__panel">
        <h2>Tus invitaciones</h2>
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>Para</th><th>Cómo</th><th>Estado</th><th>Fecha</th></tr></thead>
            <tbody>
              @foreach ($invitations as $invitation)
                <tr>
                  <td>{{ $invitation->name ?? 'Sin nombre' }}@if ($invitation->email)<br><small class="hint">{{ $invitation->email }}</small>@endif</td>
                  <td>{{ $invitation->channel === 'email' ? 'Email' : 'Enlace' }}</td>
                  <td>
                    <span @class(['badge', 'badge--ok' => $invitation->accepted_at, 'badge--nivel-1' => ! $invitation->accepted_at])>{{ $invitation->statusLabel() }}</span>
                    @if ($invitation->acceptedBy)<br><small class="hint">{{ $invitation->acceptedBy->name }}</small>@endif
                  </td>
                  <td style="white-space:nowrap">{{ $invitation->created_at->timezone(config('tinku.timezone'))->format('d/m/Y') }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </section>
    @endif
  </main>
</x-layout>
