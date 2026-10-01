<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\ExperienceStatus;
use App\Enums\HostStatus;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Media;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Eliminar la cuenta (derecho de supresión, Ley 25.326). Se borran los datos
 * personales y queda un registro mínimo: las reservas, opiniones y mensajes
 * ajenos siguen existiendo, pero ya no identifican a la persona.
 */
class AccountDeletion
{
    public function __construct(private readonly SecurityLog $securityLog) {}

    /**
     * Lo que impide eliminar la cuenta ahora.
     *
     * @return list<string>
     */
    public function blockers(User $user): array
    {
        $active = [BookingStatus::Requested, BookingStatus::Confirmed];
        $blockers = [];

        if (Booking::query()->where('user_id', $user->id)->whereIn('status', $active)->whereHas('date', fn ($q) => $q->where('starts_at', '>', now()))->exists()) {
            $blockers[] = 'Tenés reservas próximas. Cancelalas desde Tus reservas antes de eliminar la cuenta.';
        }
        if ($user->hostProfile && Booking::query()->whereIn('status', $active)
            ->whereHas('experience', fn ($q) => $q->where('host_profile_id', $user->hostProfile->id))
            ->whereHas('date', fn ($q) => $q->where('starts_at', '>', now()))->exists()) {
            $blockers[] = 'Tenés reservas de viajeros en tus experiencias. Rechazalas o esperá a que pasen.';
        }

        return $blockers;
    }

    public function delete(User $user): void
    {
        DB::transaction(function () use ($user): void {
            Media::query()->where('mediable_type', $user->getMorphClass())->where('mediable_id', $user->id)->get()->each->delete();
            $user->verifications()->delete();
            $user->interestedCategories()->detach();
            $user->interestedProvinces()->detach();
            $user->notifications()->delete();
            $user->invitations()->whereNull('accepted_at')->delete();

            if ($profile = $user->hostProfile) {
                $profile->experiences()->update(['status' => ExperienceStatus::Archived, 'meeting_address' => null, 'latitude' => null, 'longitude' => null]);
                DB::table('host_profiles')->where('id', $profile->id)->update([
                    'status' => HostStatus::Suspended->value, 'display_name' => 'Anfitrión', 'bio' => '', 'address' => null,
                    'latitude' => null, 'longitude' => null, 'payout_account' => null, 'payout_holder_name' => null,
                ]);
            }

            // Directo en la tabla: la auditoría no tiene que guardar los datos que se están borrando.
            DB::table('users')->where('id', $user->id)->update([
                'name' => 'Cuenta eliminada', 'first_name' => 'Cuenta', 'last_name' => 'eliminada',
                'email' => 'eliminada-'.$user->id.'-'.Str::lower(Str::random(8)).'@tinku.invalid',
                'phone' => null, 'birth_date' => null, 'nationality_code' => null, 'country_code' => null, 'province_id' => null,
                'city' => null, 'postal_code' => null, 'avatar_path' => null, 'dietary_needs' => null, 'food_allergies' => null, 'social_links' => null,
                'required_features' => null, 'interest_alerts' => false, 'team_role' => null,
                'password' => Hash::make(Str::random(64)), 'remember_token' => null,
                'two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null,
                'email_verified_at' => null, 'phone_verified_at' => null,
                'suspended_at' => now(), 'suspension_reason' => 'Cuenta eliminada a pedido de su titular.',
                'anonymized_at' => now(), 'updated_at' => now(),
            ]);

            // Los cambios auditados de la cuenta quedan como evento, sin los valores personales.
            AuditLog::query()->where('auditable_type', User::class)->where('auditable_id', (string) $user->id)->update(['old_values' => null, 'new_values' => null]);
        });

        $this->securityLog->record('account.deleted', $user, ['account_id' => $user->id], null, 'warning');
    }
}
