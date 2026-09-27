@props(['for' => 'password', 'confirm' => 'password_confirmation'])
{{-- Avisa en vivo si la confirmación no coincide con la contraseña. --}}
<p
  class="match"
  aria-live="polite"
  x-data="{
    first: '',
    second: '',
    init() {
      const a = document.getElementById(@js($for));
      const b = document.getElementById(@js($confirm));
      if (! a || ! b) return;
      const sync = () => { this.first = a.value; this.second = b.value; };
      a.addEventListener('input', sync);
      b.addEventListener('input', sync);
      sync();
    },
  }"
>
  <span class="match__bad" x-show="second !== '' && first !== second" x-cloak><x-icon name="x" :size="14" /> Las contraseñas no coinciden.</span>
  <span class="match__ok" x-show="second !== '' && first === second" x-cloak><x-icon name="check" :size="14" /> Las contraseñas coinciden.</span>
</p>
