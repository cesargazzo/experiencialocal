@props(['level', 'full' => false])
@php
    $level = $level instanceof \App\Enums\VerificationLevel ? $level : \App\Enums\VerificationLevel::from((int) $level);
    $icon = $level->value >= 2 ? 'seal-check' : 'check';
@endphp
<span class="badge badge--nivel-{{ $level->value }}" title="{{ $level->label() }}">
  <x-icon :name="$icon" :size="14" />@if ($full)<span>{{ $level->label() }}</span>@else<span class="sr-only">{{ $level->label() }}</span>@endif
</span>
