<x-layout :title="'Aceptaciones de la versión '.$terms->version" :noindex="true">
  <main class="container wizard" style="max-width:920px">
    <p class="eyebrow"><a href="{{ route('admin.terminos') }}">Términos y condiciones</a></p>
    <h1 class="title">Quién aceptó la <span class="hl">versión {{ $terms->version }}</span>.</h1>
    <p class="hint">Huella del texto publicado: <code>{{ $terms->body_hash }}</code></p>
    <section class="wizard__panel">
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Cuenta</th><th>Aceptó</th><th>Cómo</th><th>IP</th></tr></thead>
          <tbody>
            @forelse ($acceptances as $acceptance)
              <tr>
                <td><a href="{{ route('admin.usuarios.show', $acceptance->user) }}">{{ $acceptance->user->name }}</a><br><small class="hint">{{ $acceptance->user->email }}</small></td>
                <td style="white-space:nowrap">{{ $acceptance->accepted_at->timezone(config('tinku.timezone'))->format('d/m/Y H:i:s') }}</td>
                <td>{{ $acceptance->context === 'register' ? 'Al registrarse' : 'Al ingresar' }}</td>
                <td><small>{{ $acceptance->ip }}</small></td>
              </tr>
            @empty
              <tr><td colspan="4" class="hint">Nadie la aceptó todavía.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div style="margin-top:16px">{{ $acceptances->links() }}</div>
    </section>
  </main>
</x-layout>
