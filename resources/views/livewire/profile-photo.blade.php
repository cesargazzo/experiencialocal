<section class="wizard__panel">
  <h2>Tu foto</h2>
  <p>Ayuda a que quien te recibe, o quien reservó, te reconozca. Usá una foto tuya, de frente y con buena luz.</p>
  <div class="upload" style="margin-top:16px">
    <div class="upload__preview"><x-avatar :user="$user" :size="96" /></div>
    <div class="upload__body">
      <input id="profile-photo" class="sr-only" type="file" wire:model="photo" accept="image/jpeg,image/png,image/webp">
      <label for="profile-photo" class="btn btn--tertiary btn--sm upload__pick">{{ $user->avatar ? 'Cambiá tu foto' : 'Elegí una foto' }}</label>
      <span class="hint">JPG, PNG o WebP de al menos 200 × 200 px.</span>
      <span class="hint" wire:loading wire:target="photo">Subiendo y optimizando…</span>
      @error('photo')<span class="error" style="display:block">{{ $message }}</span>@enderror
    </div>
  </div>
</section>
