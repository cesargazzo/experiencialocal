<x-layout :title="$user->name" :noindex="true">
  <main class="container wizard" style="max-width:920px">
    <p class="eyebrow"><a href="{{ route('admin.usuarios') }}">Usuarios</a></p>
    <div class="user-head">
      <x-avatar :user="$user" :size="72" />
      <div>
        <h1 class="title" style="margin:0">{{ $user->name }}</h1>
        <p class="hint" style="margin:4px 0 0">{{ $user->email }} · {{ $user->phone ?? 'sin teléfono' }}</p>
        <p style="margin:8px 0 0;display:flex;gap:6px;flex-wrap:wrap">
          <x-verification-badge :level="$user->verification_level" full />
          @if ($user->is_admin)<span class="badge badge--nivel-1">Admin</span>@endif
          @if ($user->hostProfile)<span class="badge badge--nivel-1">Anfitrión · {{ $user->hostProfile->plan->name }}</span>@endif
          @if ($user->isSuspended())<span class="badge badge--error">Suspendida</span>@endif
        </p>
      </div>
    </div>

    <section class="wizard__panel">
      <h2>Datos</h2>
      <div class="summary">
        <div><span>Nacionalidad</span><strong>{{ $user->nationality_code ?? '—' }}</strong></div>
        <div><span>Vive en</span><strong>{{ $user->locationLabel() ?? '—' }}</strong></div>
        <div><span>Código postal</span><strong>{{ $user->postal_code ?? '—' }}</strong></div>
        <div><span>Fecha de nacimiento</span><strong>{{ $user->birth_date?->format('d/m/Y') ?? '—' }}</strong></div>
        <div><span>Alta</span><strong>{{ $user->created_at->timezone(config('tinku.timezone'))->format('d/m/Y H:i') }}</strong></div>
        <div><span>Invitada por</span><strong>{{ $user->invitedBy?->name ?? '—' }}</strong></div>
        @if ($user->isSuspended())
          <div><span>Suspendida</span><strong>{{ $user->suspended_at->timezone(config('tinku.timezone'))->format('d/m/Y H:i') }} · {{ $user->suspension_reason }}</strong></div>
        @endif
      </div>
    </section>

    <section class="wizard__panel">
      <h2>Términos aceptados</h2>
      @forelse ($user->termsAcceptances as $acceptance)
        <div class="summary"><div><span>Versión {{ $acceptance->version->version }} · {{ $acceptance->context === 'register' ? 'al registrarse' : 'al ingresar' }}</span><strong>{{ $acceptance->accepted_at->timezone(config('tinku.timezone'))->format('d/m/Y H:i') }} · {{ $acceptance->ip }}</strong></div></div>
      @empty
        <p class="hint">No aceptó ninguna versión todavía.</p>
      @endforelse
    </section>

    <section class="wizard__panel">
      <h2>Validar a mano</h2>
      <p>Aprueba las verificaciones que falten hasta el nivel elegido. El motivo queda en el registro de seguridad.</p>
      <form method="post" action="{{ route('admin.usuarios.validar') }}">
        @csrf
        <input type="hidden" name="users[]" value="{{ $user->id }}">
        <div class="grid-2">
          <div class="field"><label for="level">Nivel</label>
            <select id="level" name="level">
              @foreach ($levels as $level)
                @continue($level->value === 0)
                <option value="{{ $level->value }}" @disabled($user->verification_level->value >= $level->value)>{{ $level->value }} · {{ $level->label() }}</option>
              @endforeach
            </select>
          </div>
          <div class="field"><label for="reason">Motivo</label><input id="reason" name="reason" required minlength="5" maxlength="300" placeholder="Por ejemplo: validé el DNI en persona">@error('reason')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
        </div>
        <div class="wizard__actions"><span></span><button class="btn btn--secondary" type="submit" @disabled($user->verification_level->value >= 3)>Validá</button></div>
      </form>
    </section>

    <section class="wizard__panel">
      <h2>Verificaciones</h2>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Tipo</th><th>Proveedor</th><th>Estado</th><th>Revisó</th><th>Fecha</th></tr></thead>
          <tbody>
            @forelse ($user->verifications as $v)
              <tr>
                <td>{{ $v->type->value }}</td>
                <td>{{ $v->provider->value }}{{ $v->document_country ? ' · '.$v->document_country : '' }}</td>
                <td>{{ $v->status->value }}@if ($v->result['reason'] ?? null)<br><small class="hint">{{ $v->result['reason'] }}</small>@endif</td>
                <td>{{ $v->reviewer?->name ?? '—' }}</td>
                <td style="white-space:nowrap">{{ ($v->reviewed_at ?? $v->submitted_at)?->timezone(config('tinku.timezone'))->format('d/m/Y H:i') }}</td>
              </tr>
            @empty
              <tr><td colspan="5" class="hint">Sin verificaciones.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>

    <section class="wizard__panel">
      <h2>Actividad de seguridad</h2>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Cuándo</th><th>Evento</th><th>IP</th></tr></thead>
          <tbody>
            @forelse ($events as $event)
              <tr>
                <td style="white-space:nowrap">{{ $event->created_at->timezone(config('tinku.timezone'))->format('d/m/Y H:i') }}</td>
                <td><span class="badge badge--sev-{{ $event->severity }}">{{ $event->label() }}</span></td>
                <td><a class="link" href="{{ route('admin.seguridad', ['ip' => $event->ip]) }}"><code>{{ $event->ip }}</code></a></td>
              </tr>
            @empty
              <tr><td colspan="3" class="hint">Sin eventos.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>

    <section class="wizard__panel">
      <h2>Cambios</h2>
      <p>De esta cuenta y hechos por esta cuenta. <a class="link" href="{{ route('admin.auditoria', ['tipo' => \App\Models\User::class, 'id' => $user->id]) }}">Ver todos</a></p>
      @include('admin.partials.audit-table', ['logs' => $auditLogs])
    </section>

    @unless ($user->is(auth()->user()))
      <section class="wizard__panel">
        <h2>{{ $user->isSuspended() ? 'Reactivar la cuenta' : 'Suspender la cuenta' }}</h2>
        <p>{{ $user->isSuspended() ? 'Vuelve a poder ingresar y reservar.' : 'Pierde la sesión, no puede ingresar ni reservar. Se puede revertir.' }}</p>
        <form method="post" action="{{ route('admin.usuarios.suspension', $user) }}">
          @csrf
          @unless ($user->isSuspended())
            <div class="field"><label for="suspend-reason">Motivo</label><input id="suspend-reason" name="reason" required maxlength="300">@error('reason')<span class="error" style="display:block">{{ $message }}</span>@enderror</div>
          @endunless
          <div class="wizard__actions"><span></span><button class="btn {{ $user->isSuspended() ? 'btn--secondary' : 'btn--tertiary' }}" type="submit">{{ $user->isSuspended() ? 'Reactivá la cuenta' : 'Suspendé la cuenta' }}</button></div>
        </form>
      </section>
    @endunless
  </main>
</x-layout>
