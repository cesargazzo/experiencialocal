{{-- Datos prácticos según la categoría. Lo usan el alta y la edición de experiencias. --}}
@php($category = $this->selectedCategory())
@if ($category?->has_difficulty)
  <fieldset class="field" style="border:0;padding:0">
    <legend style="font-weight:600">Dificultad</legend>
    <div class="choice-grid">
      @foreach (\App\Enums\Difficulty::cases() as $level)
        <label class="choice">
          <input type="radio" wire:model="difficulty" value="{{ $level->value }}">
          <span style="flex-direction:column;align-items:flex-start;gap:2px"><strong>{{ $level->label() }}</strong><small class="hint" style="margin:0">{{ $level->hint() }}</small></span>
        </label>
      @endforeach
    </div>
    @error('difficulty')<span class="error" style="display:block">{{ $message }}</span>@enderror
  </fieldset>
@endif

<div class="field">
  <label for="what_to_bring">{{ $category?->has_difficulty ? 'Qué llevar y cómo vestirse' : 'Qué llevar' }} <span class="hint">(opcional)</span></label>
  <textarea id="what_to_bring" rows="2" maxlength="500" wire:model="what_to_bring" placeholder="{{ $category?->has_difficulty ? 'Zapatillas cómodas, abrigo liviano, protector solar y agua.' : 'Nada: está todo incluido. O, por ejemplo, un delantal.' }}"></textarea>
  <x-char-count :max="500" />
  @error('what_to_bring')<span class="error" style="display:block">{{ $message }}</span>@enderror
</div>

<div class="grid-2">
  <div class="field">
    <label for="min_age">Edad mínima <span class="hint">(opcional)</span></label>
    <input id="min_age" type="number" min="0" max="99" wire:model="min_age" placeholder="Sin límite">
    @error('min_age')<span class="error" style="display:block">{{ $message }}</span>@enderror
  </div>
</div>

<fieldset class="field" style="border:0;padding:0">
  <legend style="font-weight:600">Bueno saber</legend>
  <div class="choice-grid">
    @foreach (\App\Enums\ExperienceFeature::cases() as $feature)
      <label class="choice"><input type="checkbox" wire:model="features" value="{{ $feature->value }}"> <span>{{ $feature->label() }}</span></label>
    @endforeach
  </div>
  @error('features.*')<span class="error" style="display:block">{{ $message }}</span>@enderror
</fieldset>

@unless ($category?->has_food)
  <label class="toggle-row">
    <input type="checkbox" wire:model.live="includes_food">
    <span><strong>Hay algo para comer o tomar</strong><small>Por ejemplo, una picada en el camino. Así podés marcar las opciones de comida.</small></span>
  </label>
@endunless

@if ($this->showsFoodOptions())
  <fieldset class="field" style="border:0;padding:0">
    <legend style="font-weight:600">Opciones de comida</legend>
    <span class="hint">Marcá solo lo que puedas garantizar.</span>
    <div class="choice-grid">
      @foreach (\App\Enums\DietaryOption::cases() as $option)
        <label class="choice">
          <input type="checkbox" wire:model="dietary_options" value="{{ $option->value }}">
          <span style="flex-direction:column;align-items:flex-start;gap:2px"><strong>{{ $option->label() }}</strong><small class="hint" style="margin:0">{{ $option->hint() }}</small></span>
        </label>
      @endforeach
    </div>
    @error('dietary_options.*')<span class="error" style="display:block">{{ $message }}</span>@enderror
  </fieldset>
@endif
