@props(['user', 'size' => 32])
@php $photo = $user->avatar; @endphp
@if ($photo)
  <img {{ $attributes->merge(['class' => 'avatar avatar--photo']) }} src="{{ $photo->url($size > 96 ? 'md' : 'sm') }}" srcset="{{ $photo->url('sm') }} 96w, {{ $photo->url('md') }} 320w" sizes="{{ $size }}px" width="{{ $size }}" height="{{ $size }}" alt="{{ $user->publicName() }}" loading="lazy" decoding="async" style="width:{{ $size }}px;height:{{ $size }}px">
@else
  <span {{ $attributes->merge(['class' => 'avatar']) }} style="width:{{ $size }}px;height:{{ $size }}px;font-size:{{ max(11, (int) round($size * 0.36)) }}px" aria-hidden="true">{{ $user->initials() }}</span>
@endif
