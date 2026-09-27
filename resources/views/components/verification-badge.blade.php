@props(['level', 'full' => false])
@php
  $level = $level instanceof \App\Enums\VerificationLevel ? $level : \App\Enums\VerificationLevel::from((int) $level);
  [$color, $icon] = match ($level) {
    \App\Enums\VerificationLevel::Residence => ['#6b4cf6', '✓✓'],
    \App\Enums\VerificationLevel::Document => ['#14b8a6', '✓'],
    \App\Enums\VerificationLevel::Contact => ['#9aa0ab', '✓'],
    default => ['#c9c4b8', '·'],
  };
@endphp
<span class="badge" style="--c:{{ $color }}" title="{{ $level->label() }}">{{ $icon }}@if ($full) {{ $level->label() }}@endif</span>
