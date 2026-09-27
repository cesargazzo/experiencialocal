<x-layout title="Tus intereses" :noindex="true">
  <main class="container wizard" style="max-width:820px">
    <p class="eyebrow">Tu cuenta</p>
    <h1 class="title">Tus <span class="hl">intereses</span>.</h1>
    @include('account.partials.nav')

    <form method="post" action="{{ route('cuenta.intereses.update') }}">
      @csrf
      @method('put')

      <section class="wizard__panel">
        <h2>Qué te gusta hacer</h2>
        <p>Si no elegís ninguna, te avisamos de todas en los lugares que marques.</p>
        <div class="choice-grid">
          @foreach ($categories as $category)
            <label class="choice">
              <input type="checkbox" name="categories[]" value="{{ $category->id }}" @checked(in_array($category->id, old('categories', $selectedCategories)))>
              <span><x-icon :name="$category->icon" :size="20" /> {{ $category->name }}</span>
            </label>
          @endforeach
        </div>
      </section>

      <section class="wizard__panel">
        <h2>Dónde</h2>
        <p>Si no elegís ninguna provincia, te avisamos de todo el país.</p>
        @foreach ($countries as $country)
          <div class="choice-grid choice-grid--small">
            @foreach ($country->provinces as $province)
              <label class="choice">
                <input type="checkbox" name="provinces[]" value="{{ $province->id }}" @checked(in_array($province->id, old('provinces', $selectedProvinces)))>
                <span>{{ $province->name }}</span>
              </label>
            @endforeach
          </div>
        @endforeach
      </section>

      <section class="wizard__panel">
        <h2>Qué necesitás</h2>
        <p>Te avisamos solo de las experiencias donde el anfitrión indicó que lo cumple.</p>
        <div class="choice-grid">
          @foreach ($features as $feature)
            <label class="choice">
              <input type="checkbox" name="features[]" value="{{ $feature->value }}" @checked(in_array($feature->value, old('features', $selectedFeatures), true))>
              <span>{{ $feature->needLabel() }}</span>
            </label>
          @endforeach
        </div>
      </section>

      <section class="wizard__panel">
        <h2><x-icon name="bell" :size="22" /> Avisos</h2>
        <label class="toggle-row">
          <input type="checkbox" name="interest_alerts" value="1" @checked(old('interest_alerts', $user->interest_alerts))>
          <span><strong>Avisame por email cuando haya algo que coincida</strong><small>Cuando se publica una experiencia o se abren fechas nuevas. Como máximo un aviso por semana por cada experiencia.</small></span>
        </label>
        <div class="wizard__actions"><span></span><button class="btn btn--secondary" type="submit">Guardá tus intereses</button></div>
      </section>
    </form>
  </main>
</x-layout>
