<section class="wizard__panel thread" wire:poll.15s="refreshThread">
  <p class="notice" style="margin-top:0">
    <x-icon name="lock-key" :size="16" /> Mensajes privados y guardados cifrados.
    @unless ($canShareContact)
      Hasta que haya una reserva confirmada, ocultamos teléfonos, mails y enlaces: así arreglan todo dentro de Tinku y los dos quedan cubiertos.
    @endunless
  </p>

  <div class="thread__messages">
    @forelse ($messages as $message)
      @php($mine = $message->sender_id === $me->id)
      <div @class(['bubble', 'bubble--mine' => $mine]) wire:key="message-{{ $message->id }}">
        <p class="bubble__text">{{ $message->body }}</p>
        <small class="bubble__meta">
          {{ $mine ? 'Vos' : $message->sender->publicName() }} · {{ $message->created_at->timezone(config('tinku.timezone'))->format('d/m H:i') }}
          @if ($message->contact_redacted) · se ocultaron datos de contacto @endif
          @if (! $mine)
            @if ($message->reported_at)
              · denunciado
            @else
              · <button type="button" class="link-button" wire:click="startReport({{ $message->id }})">Denunciar</button>
            @endif
          @endif
        </small>
        @if ($reportingId === $message->id)
          <div class="bubble__report">
            <input class="inline-input" wire:model="reportReason" maxlength="300" placeholder="Qué pasó (lo revisa el equipo de Tinku)" aria-label="Motivo de la denuncia">
            <button type="button" class="btn btn--tertiary btn--sm" wire:click="report">Enviá la denuncia</button>
            @error('reportReason')<span class="error" style="display:block">{{ $message }}</span>@enderror
          </div>
        @endif
      </div>
    @empty
      <p class="hint">Escribile a {{ $other->first_name }}: podés preguntar por fechas, qué incluye, cómo llegar o lo que necesites.</p>
    @endforelse
  </div>

  @if ($notice)<p class="notice" role="status">{{ $notice }}</p>@endif

  <form wire:submit="send" class="thread__form">
    <label for="body" class="sr-only">Tu mensaje</label>
    <textarea id="body" rows="3" wire:model="body" maxlength="2000" placeholder="Escribí tu mensaje…" x-on:keydown.enter="if (($event.metaKey || $event.ctrlKey)) { $event.preventDefault(); $wire.send() }"></textarea>
    @error('body')<span class="error" style="display:block">{{ $message }}</span>@enderror
    <div class="wizard__actions" style="margin-top:8px"><span class="hint">Ctrl + Enter para enviar.</span><button class="btn btn--primary" type="submit" wire:loading.attr="disabled" wire:target="send">Enviá</button></div>
  </form>
</section>
