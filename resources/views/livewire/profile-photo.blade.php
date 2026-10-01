<section class="wizard__panel" @if ($waiting) wire:poll.2s="checkProcessing" @endif>
  <h2>Tu foto</h2>
  <p>Ayuda a que quien te recibe, o quien reservó, te reconozca. Usá una foto tuya, de frente y con buena luz.</p>
  <div
    class="upload"
    style="margin-top:16px"
    x-data="{ uploading: false, progress: 0 }"
    x-on:livewire-upload-start="uploading = true; progress = 0"
    x-on:livewire-upload-progress="progress = $event.detail.progress"
    x-on:livewire-upload-finish="uploading = false"
    x-on:livewire-upload-error="uploading = false"
    x-on:livewire-upload-cancel="uploading = false"
  >
    <div @class(['upload__preview', 'is-processing' => $waiting])><x-avatar :user="$user" :size="96" /></div>
    <div class="upload__body">
      <input id="profile-photo" class="sr-only" type="file" wire:model="photo" accept="image/jpeg,image/png,image/webp" @disabled($waiting)>
      <div class="upload__actions">
        <label for="profile-photo" class="btn btn--tertiary btn--sm upload__pick" @if ($waiting) aria-disabled="true" @endif>{{ $user->avatar ? 'Cambiá tu foto' : 'Elegí una foto' }}</label>
        @if ($user->avatar)
          <button type="button" class="btn btn--ghost btn--sm" wire:click="rotate" @disabled($waiting)><x-icon name="arrow-clockwise" :size="16" /> Girá</button>
          <button type="button" class="btn btn--ghost btn--sm" wire:click="remove" wire:confirm="¿Quitamos tu foto?" @disabled($waiting)><x-icon name="trash" :size="16" /> Quitá</button>
        @endif
      </div>
      <x-upload-progress />
      @if ($waiting)
        <span class="hint" role="status"><span class="spinner" aria-hidden="true"></span> Estamos preparando tu foto. Podés seguir usando Tinku.</span>
      @else
        <span class="hint">JPG, PNG o WebP de al menos 200 × 200 px.</span>
      @endif
      @error('photo')<span class="error" style="display:block">{{ $message }}</span>@enderror
      @if (! $waiting && $user->latestAvatarUpload?->status === 'rejected')
        <span class="error" style="display:block">Tu última foto no cumple las normas de Tinku y no se muestra. Probá con otra en la que se te vea a vos.</span>
      @endif
    </div>
  </div>
</section>
