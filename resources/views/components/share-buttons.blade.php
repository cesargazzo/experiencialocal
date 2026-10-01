@props(['url', 'title'])
{{-- Compartir: el menú nativo del celular si existe; si no, WhatsApp y copiar el enlace. --}}
<div {{ $attributes->class('share') }} x-data="{ copied: false, native: !! navigator.share }">
  <button type="button" class="btn btn--ghost btn--sm" x-show="native" x-cloak x-on:click="navigator.share({ title: @js($title), url: @js($url) }).catch(() => {})"><x-icon name="paper-plane-tilt" :size="16" /> {{ __('Compartir') }}</button>
  <a class="btn btn--ghost btn--sm" x-show="! native" href="https://wa.me/?text={{ rawurlencode($title.' — '.$url) }}" target="_blank" rel="noopener noreferrer"><x-icon name="whatsapp-logo" :size="16" /> WhatsApp</a>
  <button type="button" class="btn btn--ghost btn--sm" x-show="! native" x-on:click="navigator.clipboard.writeText(@js($url)).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"><x-icon name="link" :size="16" /> <span x-text="copied ? @js(__('Copiado')) : @js(__('Copiar enlace'))">{{ __('Copiar enlace') }}</span></button>
</div>
