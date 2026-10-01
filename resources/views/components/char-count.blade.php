@props(['min' => null, 'max' => null])
{{-- Cuenta los caracteres del campo que tiene al lado, dentro del mismo .field. --}}
<span class="char-count" aria-live="polite"
  x-data="{ n: 0, min: @js($min), max: @js($max), shortText: @js(__(':n de :min caracteres como mínimo. Te faltan :left.')), countText: @js(__(':n caracteres')) }"
  x-init="const field = $el.closest('.field').querySelector('textarea, input'); n = field.value.length; field.addEventListener('input', () => n = field.value.length)"
  x-bind:class="{ 'is-short': min && n < min, 'is-ok': min && n >= min }"
  x-text="min && n < min ? shortText.replace(':n', n).replace(':min', min).replace(':left', min - n) : (max ? `${n} / ${max}` : countText.replace(':n', n))"></span>
