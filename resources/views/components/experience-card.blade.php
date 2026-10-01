@props(['experience'])
@php $e = $experience; @endphp
<article class="card reveal" data-cat="{{ $e->category->slug }}">
  <a href="{{ route('experiencias.show', $e) }}" class="card__media" style="background-image:url('{{ $e->coverUrl('card') }}')" aria-hidden="true" tabindex="-1">
    <span class="card__tag"><x-icon :name="$e->category->icon" :size="14" /> {{ $e->type_label }}</span>
    <span class="card__dur"><x-icon name="clock" :size="16" /> {{ $e->durationLabel() }} · {{ $e->city }}</span>
  </a>
  <x-favorite-button :experience="$e" class="card__fav" />
  <div class="card__body">
    <div class="card__meta">
      @if ($e->reviews_count > 0)
        <span><x-stars :rating="$e->rating_avg" /> <span class="rating">{{ number_format($e->rating_avg, 1, ',', '.') }}</span> · {{ plural_es($e->reviews_count, __('opinión'), __('opiniones')) }}</span>
      @else
        <span class="is-new">{{ __('Nueva en Tinku') }}</span>
      @endif
      <span>{{ __('Hasta :count personas', ['count' => $e->max_guests]) }}</span>
    </div>
    <h3 class="card__title"><a href="{{ route('experiencias.show', $e) }}">{{ $e->title }}</a></h3>
    <p class="card__text">{{ $e->summary }}</p>
    @php($tags = collect([$e->difficulty ? __('Dificultad :level', ['level' => Str::lower($e->difficulty->label())]) : null])->merge($e->dietary_options?->map->label() ?? [])->filter())
    @if ($tags->isNotEmpty())
      <p class="card__diet">{{ $tags->join(' · ') }}</p>
    @endif
    <div class="card__foot">
      <div class="card__host"><x-avatar :user="$e->host->user" :size="32" /><span title="{{ $e->host->publicName() }}">{{ __('Con :name', ['name' => $e->host->user->first_name]) }}</span><x-verification-badge :level="$e->host->user->verification_level" /></div>
      <div class="price">{{ money($e->price) }} <small>{{ __('por persona') }}</small></div>
    </div>
  </div>
</article>
