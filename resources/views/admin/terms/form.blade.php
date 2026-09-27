<x-layout :title="$terms->exists ? 'Versión '.$terms->version : 'Nueva versión de términos'" :noindex="true">
  <main class="container wizard" style="max-width:920px">
    <p class="eyebrow"><a href="{{ route('admin.terminos') }}">Términos y condiciones</a></p>
    <h1 class="title">{{ $terms->exists ? 'Versión '.$terms->version : 'Nueva versión' }}</h1>
    @if ($terms->isPublished())
      <p class="notice">Esta versión está publicada y no se puede editar. Para cambiar algo, creá una versión nueva.</p>
    @endif
    <section class="wizard__panel">
      <form method="post" action="{{ $terms->exists ? route('admin.terminos.update', $terms) : route('admin.terminos.store') }}">
        @csrf
        @if ($terms->exists) @method('put') @endif
        <fieldset @disabled($terms->isPublished()) style="border:0;padding:0;margin:0">
          <div class="grid-2">
            <div class="field"><label for="version">Número de versión</label><input id="version" name="version" value="{{ old('version', $terms->version) }}" placeholder="1.0" required>@error('version')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
            <div class="field"><label for="title">Título</label><input id="title" name="title" value="{{ old('title', $terms->title) }}" required>@error('title')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
          </div>
          <div class="field"><label for="changes_summary">Qué cambió <span class="hint">(se muestra al pedir que la acepten)</span></label><textarea id="changes_summary" name="changes_summary" rows="3">{{ old('changes_summary', $terms->changes_summary) }}</textarea>@error('changes_summary')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
          <div class="field"><label for="body">Texto completo</label><textarea id="body" name="body" rows="24" required>{{ old('body', $terms->body) }}</textarea><span class="hint">Texto plano. Los saltos de línea se respetan.</span>@error('body')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
          <label class="toggle-row">
            <input type="hidden" name="requires_reacceptance" value="0">
            <input type="checkbox" name="requires_reacceptance" value="1" @checked(old('requires_reacceptance', $terms->requires_reacceptance))>
            <span><strong>Pedir que todas las cuentas la acepten de nuevo</strong><small>Para cambios de fondo. Si es una corrección menor (una errata, un dato de contacto), destildalo.</small></span>
          </label>
          <div class="wizard__actions"><a class="btn btn--ghost" href="{{ route('admin.terminos') }}">Volver</a><button class="btn btn--secondary" type="submit">Guardá el borrador</button></div>
        </fieldset>
      </form>
    </section>
  </main>
</x-layout>
