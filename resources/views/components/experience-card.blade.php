@props(['experience'])
@php $e = $experience; @endphp
<article class="card reveal" data-cat="{{ $e->category->slug }}">
  <a href="{{ route('experiencias.show', $e) }}" class="card__media" style="background-image:url('{{ $e->coverUrl('card') }}')" aria-hidden="true" tabindex="-1">
    <span class="card__tag"><x-icon :name="$e->category->icon" :size="14" /> {{ $e->type_label }}</span>
    <span class="card__dur"><x-icon name="clock" :size="16" /> {{ $e->durationLabel() }} · {{ $e->city }}</span>
  </a>
  <div class="card__body">
    <div class="card__meta">
      @if ($e->reviews_count > 0)
        <span><x-stars :rating="$e->rating_avg" /> <span class="rating">{{ number_format($e->rating_avg, 1, ',', '.') }}</span> · {{ plural_es($e->reviews_count, 'opinión', 'opiniones') }}</span>
      @else
        <span class="is-new">Nueva en Tinku</span>
      @endif
      <span>Hasta {{ $e->max_guests }} personas</span>
    </div>
    <h3 class="card__title"><a href="{{ route('experiencias.show', $e) }}">{{ $e->title }}</a></h3>
    <p class="card__text">{{ $e->summary }}</p>
    <div class="card__foot">
      <div class="card__host"><x-avatar :user="$e->host->user" :size="32" /><span title="{{ $e->host->display_name }}">Con {{ Str::before($e->host->display_name.' ', ' ') }}</span><x-verification-badge :level="$e->host->user->verification_level" /></div>
      <div class="price">{{ money($e->price) }} <small>por persona</small></div>
    </div>
  </div>
</article>
