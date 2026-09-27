<section class="wizard__panel">
  <h2>Tu foto</h2>
  <p>Ayuda a que quien te recibe, o quien reservó, te reconozca. Usá una foto tuya, de frente y con buena luz.</p>
  <div class="upload" style="margin-top:16px">
    <div class="upload__preview"><x-avatar :user="$user" :size="96" /></div>
    <div class="upload__body">
      <label for="profile-photo" class="sr-only">Elegí una foto</label>
      <input id="profile-photo" type="file" wire:model="photo" accept="image/jpeg,image/png,image/webp">
      <span class="hint">JPG, PNG o WebP de al menos 200 × 200 px.</span>
      <span class="hint" wire:loading wire:target="photo">Subiendo y optimizando…</span>
      @error('photo')<span class="error" style="display:block">{{ $message }}</span>@enderror
      @if (session('photo-status'))<span class="hint" role="status" style="color:var(--texto-petroleo);font-weight:600">{{ session('photo-status') }}</span>@endif
    </div>
  </div>
</section>
