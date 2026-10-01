@props(['links', 'showPrivacy' => false])
@if ($links->isNotEmpty())
  <ul {{ $attributes->class('social-links') }}>
    @foreach ($links as $link)
      <li>
        <a href="{{ $link['url'] }}" target="_blank" rel="nofollow noopener noreferrer ugc">
          <x-icon name="link" :size="14" /> <span class="social-links__network">{{ $link['network']->label() }}</span> {{ $link['display'] }}
        </a>
        @if ($showPrivacy && ! $link['public'])<small class="hint">({{ __('privada') }})</small>@endif
      </li>
    @endforeach
  </ul>
@endif
