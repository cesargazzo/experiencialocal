<x-layout :title="__('Mensajes')" :noindex="true">
  <main class="container wizard" style="max-width:820px">
    <p class="eyebrow">{{ __('Tu cuenta') }}</p>
    <h1 class="title">{!! __('Tus <span class="hl">mensajes</span>.') !!}</h1>
    <p>{{ __('Conversaciones privadas con anfitriones y viajeros. Solo las leen las dos personas que participan.') }}</p>

    <section class="wizard__panel">
      @forelse ($conversations as $conversation)
        @php($other = $conversation->otherParticipant($user))
        @php($unread = $conversation->hasUnreadFor($user))
        <a @class(['conversation-row', 'is-unread' => $unread]) href="{{ route('mensajes.show', $conversation) }}">
          <x-avatar :user="$other" :size="44" />
          <span class="conversation-row__main">
            <strong>{{ $other->publicName() }}</strong>
            <small class="hint">{{ $conversation->experience->title }} · {{ $user->id === $conversation->host_user_id ? __('te consulta como viajero') : __('anfitrión') }}</small>
            <span class="conversation-row__preview">{{ $conversation->latestMessage?->sender_id === $user->id ? __('Vos:').' ' : '' }}{{ Str::limit($conversation->latestMessage?->body, 90) }}</span>
          </span>
          <small class="hint conversation-row__when">{{ $conversation->last_message_at?->diffForHumans() }}</small>
        </a>
      @empty
        <p class="hint">{{ __('Todavía no tenés mensajes. En cada experiencia podés escribirle al anfitrión antes de reservar.') }}</p>
      @endforelse
    </section>
  </main>
</x-layout>
