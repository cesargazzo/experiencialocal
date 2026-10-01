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
          @if ($user->team_role)<span class="badge badge--nivel-1">Equipo · {{ $user->team_role->label() }}</span>@endif
          @if ($user->hostProfile)<span class="badge badge--nivel-1">Anfitrión · {{ $user->hostProfile->plan->name }}</span>@endif
          @if ($user->isSuspended())<span class="badge badge--error">Suspendida</span>@endif
        </p>
      </div>
    </div>

    @if ($samePhone->isNotEmpty())
      <p class="notice" role="alert">El mismo teléfono está en otra{{ $samePhone->count() > 1 ? 's' : '' }} cuenta{{ $samePhone->count() > 1 ? 's' : '' }}:
        @foreach ($samePhone as $other)<a href="{{ route('admin.usuarios.show', $other) }}">{{ $other->name }}</a> ({{ $other->email }})@if (! $loop->last) · @endif @endforeach
      </p>
    @endif

    <section class="wizard__panel">
      <h2>Datos</h2>
      <div class="summary">
        <div><span>Nacionalidad</span><strong>{{ $user->nationality_code ?? '—' }}</strong></div>
        <div><span>Vive en</span><strong>{{ $user->locationLabel() ?? '—' }}</strong></div>
        <div><span>Código postal</span><strong>{{ $user->postal_code ?? '—' }}</strong></div>
        <div><span>Fecha de nacimiento</span><strong>{{ $user->birth_date?->format('d/m/Y') ?? '—' }}</strong></div>
        <div><span>Alta</span><strong>{{ $user->created_at->timezone(config('tinku.timezone'))->format('d/m/Y H:i') }}</strong></div>
        <div><span>Invitada por</span><strong>{{ $user->invitedBy?->name ?? '—' }}</strong></div>
        @if ($user->socialLinks()->isNotEmpty())
          <div><span>Redes y web</span><x-social-links :links="$user->socialLinks()" :show-privacy="true" /></div>
        @endif
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

    <section class="wizard__panel" id="reservas">
      <h2>Reservas</h2>
      @php($noShowCount = $user->recentNoShowsCount())
      <p class="hint">
        No se presentó a {{ plural_es($noShowCount, 'experiencia', 'experiencias') }} en los últimos {{ config('tinku.no_shows.months') }} meses.
        @if ($user->hasReachedNoShowLimit())<strong>Llegó al límite de {{ config('tinku.no_shows.limit') }}: no puede pedir reservas nuevas.</strong> Revertí las ausencias mal marcadas para habilitarla.@endif
      </p>
      @error('booking')<p class="notice" role="alert">{{ $message }}</p>@enderror
      @forelse ($bookings as $booking)
        <div class="summary">
          <div>
            <span>{{ $booking->date->localStart()->format('d/m/Y H:i') }} · {{ $booking->code }} · <a href="{{ route('experiencias.show', $booking->experience) }}">{{ $booking->experience->title }}</a></span>
            <strong>{{ $booking->status->label() }}@if ($booking->no_show_at) · marcado el {{ $booking->no_show_at->timezone(config('tinku.timezone'))->format('d/m/Y H:i') }}@endif</strong>
          </div>
        </div>
        @if ($booking->status === \App\Enums\BookingStatus::NoShow && auth()->user()->can('team.users.suspend'))
          <form method="post" action="{{ route('admin.usuarios.reservas.revertir-ausencia', [$user, $booking]) }}" class="inline-form" style="display:flex;gap:8px;align-items:flex-end;margin:4px 0 12px">
            @csrf
            <div class="field" style="flex:1;margin:0"><label for="revert-{{ $booking->id }}">Qué revisaste</label><input id="revert-{{ $booking->id }}" name="reason" required minlength="5" maxlength="300" placeholder="Ej.: el anfitrión confirmó que sí fue"></div>
            <button class="btn btn--secondary btn--sm" type="submit">Revertí la ausencia</button>
          </form>
        @endif
      @empty
        <p class="hint">No hizo reservas.</p>
      @endforelse
      @error('reason', 'noShow')<span class="error" style="display:block">{{ $message }}</span>@enderror
    </section>

    @can('team.users.verify')
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
        @unless ($user->verification_level->atLeast(\App\Enums\VerificationLevel::Document))
          <div class="grid-2">
            <div class="field"><label for="document_country">País del documento</label>
              <select id="document_country" name="document_country"><x-country-options :selected="old('document_country', $user->nationality_code ?? 'AR')" /></select>
            </div>
            <div class="field"><label for="document_number">Número de documento</label><input id="document_number" name="document_number" value="{{ old('document_number') }}" inputmode="numeric" autocomplete="off" maxlength="40"><span class="hint">Obligatorio para validar el nivel 2 o 3 si la persona no cargó su documento. No se guarda el número: solo su huella, para que no se repita en otra cuenta.</span></div>
          </div>
        @endunless
        @if ($errors->has('users'))
          <div class="notice" role="alert">@foreach ($errors->get('users') as $problem)<p style="margin:0">{{ $problem }}</p>@endforeach</div>
        @endif
        <div class="wizard__actions"><span></span><button class="btn btn--secondary" type="submit" @disabled($user->verification_level->value >= 3)>Validá</button></div>
      </form>
    </section>
    @endcan

    <section class="wizard__panel">
      <h2>Verificaciones</h2>
      @error('revoke')<p class="notice" role="alert">{{ $message }}</p>@enderror
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Tipo</th><th>Proveedor</th><th>Estado</th><th>Revisó</th><th>Fecha</th><th></th></tr></thead>
          <tbody>
            @forelse ($user->verifications as $v)
              <tr>
                <td>{{ $v->type->value }}</td>
                <td>{{ $v->provider->value }}{{ $v->document_country ? ' · '.$v->document_country : '' }}</td>
                <td>{{ $v->status->value }}@if ($v->result['reason'] ?? null)<br><small class="hint">{{ $v->result['reason'] }}</small>@endif</td>
                <td>{{ $v->reviewer?->name ?? '—' }}</td>
                <td style="white-space:nowrap">{{ ($v->reviewed_at ?? $v->submitted_at)?->timezone(config('tinku.timezone'))->format('d/m/Y H:i') }}</td>
                <td>
                  @if ($v->status === \App\Enums\VerificationStatus::Approved && auth()->user()->can('team.users.verify'))
                    <details class="revoke"><summary>Revocar</summary>
                      <form method="post" action="{{ route('admin.usuarios.verificaciones.revocar', [$user, $v]) }}" style="display:flex;gap:6px;margin-top:6px">@csrf<input name="reason" class="inline-input" minlength="5" maxlength="300" placeholder="Motivo" aria-label="Motivo de la revocación" required><button class="btn btn--tertiary btn--sm">Revocá</button></form>
                    </details>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="6" class="hint">Sin verificaciones.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>

    @can('team.security.view')
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
    @endcan

    @can('team.security.view')
    <section class="wizard__panel">
      <h2>Cambios</h2>
      <p>De esta cuenta y hechos por esta cuenta. <a class="link" href="{{ route('admin.auditoria', ['tipo' => \App\Models\User::class, 'id' => $user->id]) }}">Ver todos</a></p>
      @include('admin.partials.audit-table', ['logs' => $auditLogs])
    </section>
    @endcan

    @can('team.platform.manage')
      <section class="wizard__panel" id="equipo">
        <h2>Rol en el equipo</h2>
        <p>Quien tiene un rol entra a la administración y ve solo las secciones de su rol. Hace falta la identidad validada y el doble factor.</p>
        @if ($user->is(auth()->user()))
          <p class="hint">Tu propio rol lo cambia otra persona con administración total.</p>
        @else
          <form method="post" action="{{ route('admin.usuarios.rol', $user) }}">
            @csrf
            @method('put')
            <div class="field"><label for="team_role">Rol</label>
              <select id="team_role" name="team_role">
                <option value="">Sin rol (no es del equipo)</option>
                @foreach (\App\Enums\TeamRole::cases() as $role)
                  <option value="{{ $role->value }}" @selected($user->team_role === $role)>{{ $role->label() }} — {{ $role->description() }}</option>
                @endforeach
              </select>
              @error('team_role')<span class="error" style="display:block">{{ $message }}</span>@enderror
            </div>
            <div class="wizard__actions"><span></span><button class="btn btn--secondary" type="submit">Guardá el rol</button></div>
          </form>
        @endif
      </section>
    @endcan

    @if (! $user->is(auth()->user()) && auth()->user()->can('team.users.suspend'))
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
    @endif
  </main>
</x-layout>
