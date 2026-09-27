<x-layout title="Avisos" :noindex="true">
  <main class="container wizard" style="max-width:820px">
    <p class="eyebrow">Tu cuenta</p>
    <h1 class="title">Tus <span class="hl">avisos</span>.</h1>
    @include('account.partials.nav')

    <section class="wizard__panel">
      @forelse ($notifications as $notification)
        <a @class(['notification', 'is-unread' => in_array($notification->id, $unreadIds, true)]) href="{{ $notification->data['url'] ?? '#' }}">
          <x-icon :name="$notification->data['icon'] ?? 'bell'" :size="22" />
          <span>
            <strong>{{ $notification->data['title'] ?? 'Aviso' }}</strong>
            <span>{{ $notification->data['body'] ?? '' }}</span>
            <small class="hint">{{ $notification->created_at->diffForHumans() }}</small>
          </span>
        </a>
      @empty
        <p class="hint">No tenés avisos todavía. Acá vas a ver cuando aprueben tu experiencia, cuando haya novedades de tus reservas y lo que coincida con tus intereses.</p>
      @endforelse
    </section>
  </main>
</x-layout>
