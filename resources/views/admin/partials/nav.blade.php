<nav class="admin-nav" aria-label="Administración">
  @foreach (\App\Support\AdminSections::for(auth()->user()) as $section)
    @php($badge = match ($section['route']) {
        'admin.experiencias' => \App\Models\Experience::where('status', \App\Enums\ExperienceStatus::InReview)->whereNull('approved_at')->count(),
        'admin.denuncias' => \App\Models\Message::whereNotNull('reported_at')->count(),
        'admin.anunciantes' => \App\Models\AdvertiserInquiry::where('status', 'new')->count(),
        default => 0,
    })
    <a @class(['chip', 'is-active' => request()->routeIs($section['active'])]) href="{{ route($section['route']) }}">{{ $section['label'] }}@if ($badge) ({{ $badge }})@endif</a>
  @endforeach
</nav>
@unless (\App\Models\User::twoFactorAvailable())
  <p class="notice" role="alert" style="margin:0 0 var(--esp-3)"><x-icon name="shield-check" :size="16" /> <strong>El doble factor está apagado</strong> (TINKU_TWO_FACTOR=false): la administración no pide código. Prendelo antes de salir a producción.</p>
@endunless
