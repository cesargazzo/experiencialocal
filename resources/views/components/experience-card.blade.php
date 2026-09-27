@props(['experience'])
@php $e = $experience; @endphp
<article class="card reveal" data-cat="{{ $e->category->slug }}">
  <a href="{{ route('experiencias.show', $e) }}" class="card__media" style="background-image:url('{{ $e->cover_image_url }}')">
    <span class="card__tag">{{ $e->type_label }}</span>
    <span class="card__dur">⏱ {{ $e->durationLabel() }} · {{ $e->city }}</span>
  </a>
  <div class="card__body">
    <div class="card__meta">
      <span><x-stars :rating="$e->rating_avg" /><b> {{ number_format($e->rating_avg, 1, ',', '.') }}</b> · {{ plural_es($e->reviews_count, 'opinión', 'opiniones') }}</span>
      <span>Hasta {{ $e->max_guests }}</span>
    </div>
    <h3 class="card__title"><a href="{{ route('experiencias.show', $e) }}">{{ $e->title }}</a></h3>
    <p class="card__text">{{ $e->summary }}</p>
    <div class="card__foot">
      <div class="card__host"><span class="avatar">{{ $e->host->user->initials() }}</span><span>{{ $e->host->display_name }}</span><x-verification-badge :level="$e->host->user->verification_level" /></div>
      <div class="price">{{ money($e->price) }} <small>/ persona</small></div>
    </div>
  </div>
</article>
