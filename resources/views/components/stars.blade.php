@props(['rating'])
@php $r = (int) round((float) $rating); @endphp
<span class="stars" aria-hidden="true">{{ str_repeat('★', $r) }}{{ str_repeat('☆', 5 - $r) }}</span>
