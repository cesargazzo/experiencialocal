@props(['for' => 'password'])
@php $checks = \App\Support\PasswordPolicy::current()->clientChecks(); @endphp
{{-- Se marca en vivo mientras se escribe. El servidor vuelve a validar al guardar. --}}
<ul
  {{ $attributes->merge(['class' => 'requirements']) }}
  x-data="{
    value: '',
    checks: @js($checks),
    init() {
      const input = document.getElementById(@js($for));
      if (! input) return;
      this.value = input.value;
      input.addEventListener('input', () => this.value = input.value);
    },
    state(check) {
      if (check.serverOnly) return 'pending';
      if (this.value === '') return 'empty';
      const lengthOk = ! check.minLength || [...this.value].length >= check.minLength;
      const patternsOk = (check.patterns || []).every((p) => new RegExp(p, 'u').test(this.value));
      return lengthOk && patternsOk ? 'ok' : 'missing';
    },
  }"
>
  @foreach ($checks as $index => $check)
    <li class="requirement" x-bind:data-state="state(checks[{{ $index }}])">
      <span class="requirement__icon requirement__icon--ok"><x-icon name="check" :size="14" /></span>
      <span class="requirement__icon requirement__icon--missing"><x-icon name="x" :size="14" /></span>
      <span class="requirement__icon requirement__icon--empty"><x-icon name="circle" :size="12" /></span>
      <span>{{ $check['label'] }}</span>
      <span class="sr-only" x-text="{ ok: @js(__('cumplido')), missing: @js(__('falta')), empty: '', pending: '' }[state(checks[{{ $index }}])]"></span>
    </li>
  @endforeach
</ul>
