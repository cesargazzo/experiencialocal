<section class="wizard__panel thread" wire:poll.15s="refreshThread">
  <p class="notice" style="margin-top:0">
    <x-icon name="lock-key" :size="16" /> {{ __('Mensajes privados y guardados cifrados.') }}
    @unless ($canShareContact)
      {{ __('Hasta que haya una reserva confirmada, ocultamos teléfonos, mails y enlaces: así arreglan todo dentro de Tinku y los dos quedan cubiertos.') }}
    @endunless
  </p>

  <div class="thread__messages">
    @forelse ($messages as $message)
      @php($mine = $message->sender_id === $me->id)
      <div @class(['bubble', 'bubble--mine' => $mine]) wire:key="message-{{ $message->id }}">
        @if ($message->hidden_at && ! $mine)
          <p class="bubble__text hint"><em>{{ __('Retuvimos este mensaje para revisarlo. Si está todo bien, lo vas a ver acá.') }}</em></p>
        @else
          <p class="bubble__text">{{ $message->body }}</p>
          @if ($message->hidden_at)<p class="hint" style="margin:4px 0 0"><em>{{ __('Lo retuvimos para revisarlo: la otra persona todavía no lo ve.') }}</em></p>@endif
        @endif
        <small class="bubble__meta">
          {{ $mine ? __('Vos') : $message->sender->publicName() }} · {{ $message->created_at->timezone(config('tinku.timezone'))->format('d/m H:i') }}
          @if ($message->contact_redacted) · {{ __('se ocultaron datos de contacto') }} @endif
          @if (! $mine && ! $message->hidden_at)
            @if ($message->reported_at)
              · {{ __('denunciado') }}
            @else
              · <button type="button" class="link-button" wire:click="startReport({{ $message->id }})">{{ __('Denunciar') }}</button>
            @endif
          @endif
        </small>
        @if ($reportingId === $message->id)
          <div class="bubble__report">
            <input class="inline-input" wire:model="reportReason" maxlength="300" placeholder="{{ __('Qué pasó (lo revisa el equipo de Tinku)') }}" aria-label="{{ __('Motivo de la denuncia') }}">
            <button type="button" class="btn btn--tertiary btn--sm" wire:click="report">{{ __('Enviá la denuncia') }}</button>
            @error('reportReason')<span class="error" style="display:block">{{ $message }}</span>@enderror
          </div>
        @endif
      </div>
    @empty
      <p class="hint">{{ __('Escribile a :name: podés preguntar por fechas, qué incluye, cómo llegar o lo que necesites.', ['name' => $other->first_name]) }}</p>
    @endforelse
  </div>

  @if ($notice)<p class="notice" role="status">{{ $notice }}</p>@endif

  <form wire:submit="send" class="thread__form">
    <label for="body" class="sr-only">{{ __('Tu mensaje') }}</label>
    <textarea id="body" rows="3" wire:model="body" maxlength="2000" placeholder="{{ __('Escribí tu mensaje…') }}" x-on:keydown.enter="if (($event.metaKey || $event.ctrlKey)) { $event.preventDefault(); $wire.send() }"></textarea>
    @error('body')<span class="error" style="display:block">{{ $message }}</span>@enderror
    <div class="wizard__actions" style="margin-top:8px"><span class="hint">{{ __('Ctrl + Enter para enviar.') }}</span><button class="btn btn--primary" type="submit" wire:loading.attr="disabled" wire:target="send">{{ __('Enviá') }}</button></div>
  </form>
</section>
