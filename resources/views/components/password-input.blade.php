@props(['name', 'id' => null, 'autocomplete' => 'current-password', 'required' => true])
@php $id ??= $name; @endphp
<div class="password-field" x-data="{ visible: false }">
  <input
    {{ $attributes->merge(['class' => 'password-field__input']) }}
    id="{{ $id }}"
    name="{{ $name }}"
    type="password"
    x-bind:type="visible ? 'text' : 'password'"
    autocomplete="{{ $autocomplete }}"
    autocapitalize="off"
    spellcheck="false"
    @required($required)
  >
  <button
    type="button"
    class="password-field__toggle"
    x-on:click="visible = ! visible"
    x-bind:aria-pressed="visible.toString()"
    x-bind:aria-label="visible ? @js(__('Ocultar contraseña')) : @js(__('Mostrar contraseña'))"
    aria-label="{{ __('Mostrar contraseña') }}"
    aria-controls="{{ $id }}"
  >
    <span x-show="! visible"><x-icon name="eye" :size="20" /></span>
    <span x-show="visible" x-cloak><x-icon name="eye-slash" :size="20" /></span>
  </button>
</div>
