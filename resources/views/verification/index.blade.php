<x-layout title="Verificación de identidad" :noindex="true">
  <main class="container wizard" style="max-width:820px">
    <p class="eyebrow">Tu identidad</p>
    <h1 class="title">Nivel <span class="hl">{{ $user->verification_level->value }}</span> · {{ $user->verification_level->label() }}</h1>
    <p class="lead">La identidad es el eje de Tinku. Cada nivel habilita más acciones y es el mismo para cualquier nacionalidad.</p>
    @include('account.partials.nav')

    <div class="steps" style="grid-template-columns:repeat(3,1fr)">
      @foreach ([1 => ['Navegar y guardar favoritas', $smsVerification ? 'Email y teléfono confirmados con código.' : 'Email confirmado con código.'], 2 => ['Reservar, pagar y publicar en revisión', 'Documento validado más selfie con prueba de vida.'], 3 => ['Publicar y cobrar sin restricciones', 'Domicilio validado y revisión de un administrador.']] as $lvl => [$enables, $how])
        @php $lvlEnum = \App\Enums\VerificationLevel::from($lvl); @endphp
        <div @class(['step', 'step--done' => $user->hasVerificationLevel($lvlEnum)])>
          <div class="step__num">0{{ $lvl }}</div>
          <h3 class="step__title">{{ $lvlEnum->label() }} @if ($user->hasVerificationLevel($lvlEnum))<x-icon name="check-circle" :size="22" label="Completo" />@endif</h3>
          <p><strong>{{ $enables }}.</strong> {{ $how }}</p>
        </div>
      @endforeach
    </div>

    @error('type')<p class="error" style="display:block">{{ $message }}</p>@enderror
    @error('code')<p class="error" style="display:block">{{ $message }}</p>@enderror
    @error('document_number')<p class="error" style="display:block">{{ $message }}</p>@enderror

    <section class="wizard__panel">
      <h2>Verificaciones</h2>
      <p>Proveedor según el país del documento: argentinos por RENAPER, el resto por un proveedor internacional. El nivel 3 lo aprueba una persona del equipo.</p>
      <div class="summary">
        @foreach ($types as $type)
          @php $v = $latest->get($type->value); @endphp
          <div style="align-items:center">
            <span><strong>{{ ['email' => 'Email', 'phone' => 'Teléfono', 'document' => 'Documento de identidad', 'liveness' => 'Selfie con prueba de vida', 'address' => 'Comprobante de domicilio', 'interview' => 'Entrevista (a pedido)'][$type->value] }}</strong><br><small>Nivel {{ $type->level()->value }}{{ $v ? ' · '.$v->provider->value : '' }}</small></span>
            <span>
              @if ($v?->isApproved())
                <span class="badge badge--ok"><x-icon name="check" :size="14" /> Aprobada</span>
              @elseif ($v?->status === \App\Enums\VerificationStatus::Pending && $v->provider === \App\Enums\VerificationProvider::Internal)
                <form method="post" action="{{ route('verificacion.confirmar') }}" style="display:flex;gap:6px;align-items:center">@csrf<input type="hidden" name="type" value="{{ $type->value }}">
                  <input name="code" inputmode="numeric" pattern="[0-9]{6}" placeholder="Código" required class="inline-input" style="width:110px" aria-label="Código de seis dígitos" value="{{ app()->isProduction() ? '' : ($v->result['demo_code'] ?? '') }}">
                  <button class="btn btn--secondary btn--sm" type="submit">Confirmá</button>
                </form>
                @unless (app()->isProduction())<small class="hint">Código de prueba: {{ $v->result['demo_code'] ?? '' }}</small>@endunless
              @elseif ($v?->status === \App\Enums\VerificationStatus::Pending)
                <span class="badge badge--espera">En revisión</span>
              @elseif ($v?->status === \App\Enums\VerificationStatus::Rejected)
                <span class="badge badge--error" title="{{ $v->rejection_reason }}">Rechazada</span>
              @endif
              @if (! $v?->isApproved() && $v?->status !== \App\Enums\VerificationStatus::Pending && $type !== \App\Enums\VerificationType::Interview)
                @if ($type === \App\Enums\VerificationType::Document)
                  <details style="display:inline-block"><summary class="btn btn--tertiary btn--sm" style="list-style:none;cursor:pointer">Validá tu documento</summary>
                    <form method="post" action="{{ route('verificacion.store') }}" style="margin-top:12px;text-align:left">@csrf<input type="hidden" name="type" value="document">
                      <div class="field"><label for="doc-country">País que lo emitió</label><input id="doc-country" name="document_country" value="{{ old('document_country', $user->nationality_code ?? 'AR') }}" maxlength="2" required></div>
                      <div class="field"><label for="doc-type">Tipo</label><select id="doc-type" name="document_type"><option value="dni">DNI</option><option value="passport">Pasaporte</option><option value="national_id">Documento nacional</option></select></div>
                      <div class="field"><label for="doc-number">Número</label><input id="doc-number" name="document_number" required><span class="hint">No se guarda en claro; solo un hash para evitar duplicados.</span></div>
                      <button class="btn btn--secondary btn--sm" type="submit" style="margin-top:12px">Enviá</button>
                    </form></details>
                @else
                  <form method="post" action="{{ route('verificacion.store') }}" style="display:inline">@csrf<input type="hidden" name="type" value="{{ $type->value }}"><button class="btn btn--tertiary btn--sm" type="submit">{{ $type->level()->value === 3 ? 'Enviá el comprobante' : ($type === \App\Enums\VerificationType::Liveness ? 'Sacate la selfie' : 'Pedí el código') }}</button></form>
                @endif
              @endif
            </span>
          </div>
        @endforeach
      </div>
    </section>
  </main>
</x-layout>
