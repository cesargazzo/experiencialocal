<x-layout :title="'Mensajes · '.$conversation->experience->title" :noindex="true">
  <main class="container wizard" style="max-width:820px">
    <p class="eyebrow"><a href="{{ route('mensajes') }}">Mensajes</a></p>
    @php($other = $conversation->otherParticipant(auth()->user()))
    <h1 class="title" style="font-size:clamp(28px,4vw,40px)">{{ $other->publicName() }}</h1>
    <p class="hint">Sobre <a href="{{ route('experiencias.show', $conversation->experience) }}">{{ $conversation->experience->title }}</a></p>
    <livewire:conversation-thread :conversation="$conversation" />
  </main>
</x-layout>
