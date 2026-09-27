<x-layout title="Términos y condiciones" :noindex="true">
  <main class="container wizard" style="max-width:920px">
    <p class="eyebrow">Administración</p>
    <h1 class="title">Términos y <span class="hl">condiciones</span>.</h1>
    @include('admin.partials.nav')

    <section class="wizard__panel">
      <div class="host-head">
        <div>
          <h2 style="margin:0">Versiones</h2>
          <p class="hint" style="margin:4px 0 0">
            @if ($current)
              Vigente: {{ $current->version }}.
              @if ($required) {{ plural_es($pendingCount, 'cuenta tiene', 'cuentas tienen') }} que aceptar la versión {{ $required->version }} o posterior (de {{ $usersCount }}).@endif
            @else
              Todavía no hay una versión publicada: el registro no pide aceptar términos.
            @endif
          </p>
        </div>
        <a class="btn btn--primary btn--sm" href="{{ route('admin.terminos.create') }}">Nueva versión</a>
      </div>
      <p class="hint">Un borrador se puede editar. Al publicarlo queda fijo y se guarda una huella del texto: es la prueba de lo que aceptó cada persona. Si la versión pide aceptar de nuevo, cada cuenta la acepta en su próximo ingreso.</p>

      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Versión</th><th>Estado</th><th>Aceptar de nuevo</th><th>Aceptaciones</th><th></th></tr></thead>
          <tbody>
            @forelse ($versions as $version)
              <tr>
                <td><strong>{{ $version->version }}</strong><br><small class="hint">{{ $version->title }}</small></td>
                <td>
                  @if ($version->isPublished())
                    <span class="badge badge--ok">Publicada</span><br><small class="hint">{{ $version->published_at->timezone(config('tinku.timezone'))->format('d/m/Y H:i') }}{{ $version->publisher ? ' · '.$version->publisher->name : '' }}</small>
                  @else
                    <span class="badge badge--espera">Borrador</span>
                  @endif
                </td>
                <td>{{ $version->requires_reacceptance ? 'Sí' : 'No' }}</td>
                <td>@if ($version->isPublished())<a href="{{ route('admin.terminos.aceptaciones', $version) }}">{{ $version->acceptances_count }}</a>@else — @endif</td>
                <td style="white-space:nowrap">
                  @if ($version->isPublished())
                    <a href="{{ route('terminos.version', $version) }}">Ver</a>
                  @else
                    <a class="btn btn--tertiary btn--sm" href="{{ route('admin.terminos.edit', $version) }}">Editar</a>
                    <form method="post" action="{{ route('admin.terminos.publicar', $version) }}" style="display:inline" onsubmit="return confirm('¿Publicamos la versión {{ $version->version }}? Después no se puede editar.')">@csrf<button class="btn btn--secondary btn--sm">Publicar</button></form>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="5" class="hint">No hay versiones. Creá la primera.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>
  </main>
</x-layout>
