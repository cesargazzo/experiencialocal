<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VerificationLevel;
use App\Enums\VerificationStatus;
use App\Enums\VerificationType;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Services\SecurityLog;
use App\Services\VerificationService;
use App\Support\PlatformSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformSettingsController extends Controller
{
    public function edit(): View
    {
        $settings = PlatformSettings::current();

        return view('admin.platform-settings', [
            'settings' => $settings,
            // Cuentas con email confirmado pero sin teléfono confirmado: son las que cambiarían de nivel.
            'withoutConfirmedPhone' => User::query()
                ->whereHas('verifications', fn ($q) => $q->where('type', VerificationType::Email)->where('status', VerificationStatus::Approved))
                ->whereDoesntHave('verifications', fn ($q) => $q->where('type', VerificationType::Phone)->where('status', VerificationStatus::Approved))
                ->count(),
        ]);
    }

    public function update(Request $request, VerificationService $verifications, SecurityLog $securityLog): RedirectResponse
    {
        $before = PlatformSettings::current();
        $after = new PlatformSettings(smsVerification: $request->boolean('sms_verification'));

        Setting::store(PlatformSettings::SETTING_KEY, $after->toArray(), $request->user());

        $changed = 0;
        if ($before->smsVerification !== $after->smsVerification) {
            // La regla del nivel 1 cambió: se recalcula para todas las cuentas.
            User::query()->where('verification_level', '<=', VerificationLevel::Residence->value)->chunkById(200, function ($users) use ($verifications, &$changed) {
                foreach ($users as $user) {
                    $previous = $user->verification_level;
                    if ($verifications->recalculateLevel($user) !== $previous) {
                        $changed++;
                    }
                }
            });
        }

        $securityLog->record('settings.updated', $request->user(), ['antes' => $before->toArray(), 'después' => $after->toArray(), 'cuentas_recalculadas' => $changed], null, 'warning');

        return back()->with('status', $changed ? "Guardamos la configuración. Cambió el nivel de {$changed} cuentas." : 'Guardamos la configuración.');
    }
}
