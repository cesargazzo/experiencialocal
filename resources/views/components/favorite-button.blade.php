@props(['experience', 'withLabel' => false])
{{-- Corazón para guardar la experiencia. Sin sesión lleva a ingresar. --}}
@auth
  @php($saved = auth()->user()->hasFavorited($experience))
  <form method="post" action="{{ route('favoritas.toggle', $experience) }}" {{ $attributes->class(['fav', 'fav--labeled' => $withLabel]) }}
    x-data="{ saved: @js($saved), busy: false }"
    x-on:submit.prevent="if (busy) return; busy = true; fetch($el.action, { method: 'POST', headers: { 'X-CSRF-TOKEN': $el.querySelector('[name=_token]').value, 'Accept': 'application/json' } }).then(r => r.ok ? r.json() : Promise.reject()).then(d => saved = d.favorite).catch(() => $el.submit()).finally(() => busy = false)">
    @csrf
    <button type="submit" class="fav__button" x-bind:class="saved && 'is-saved'" x-bind:aria-pressed="saved" @class(['is-saved' => $saved]) aria-pressed="{{ $saved ? 'true' : 'false' }}"
      x-bind:aria-label="saved ? @js(__('Sacar de favoritas')) : @js(__('Guardar en favoritas'))" aria-label="{{ $saved ? __('Sacar de favoritas') : __('Guardar en favoritas') }}">
      <span x-show="! saved" @if ($saved) style="display:none" @endif><x-icon name="heart" :size="20" /></span><span x-show="saved" @unless ($saved) style="display:none" @endunless><x-icon name="heart-fill" :size="20" /></span>@if ($withLabel)<span x-text="saved ? @js(__('Guardada')) : @js(__('Guardar'))">{{ $saved ? __('Guardada') : __('Guardar') }}</span>@endif
    </button>
  </form>
@else
  <a href="{{ route('login') }}" {{ $attributes->class(['fav', 'fav--labeled' => $withLabel]) }} aria-label="{{ __('Ingresá para guardarla') }}"><span class="fav__button"><x-icon name="heart" :size="20" />@if ($withLabel)<span>{{ __('Guardar') }}</span>@endif</span></a>
@endauth
