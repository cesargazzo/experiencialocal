<?php

namespace App\Services;

use App\Enums\ExperienceStatus;
use App\Enums\HostStatus;
use App\Enums\VerificationLevel;
use App\Enums\VerificationProvider;
use App\Enums\VerificationStatus;
use App\Enums\VerificationType;
use App\Exceptions\VerificationException;
use App\Models\IdentityVerification;
use App\Models\User;
use App\Notifications\VerificationCodeNotification;
use App\Support\PlatformSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Orquesta la verificación de identidad por niveles. El usuario ve un solo
 * flujo; el proveedor se elige según el país del documento.
 */
class VerificationService
{
    /**
     * Registra un envío de verificación pendiente. Para documentos, el número
     * nunca se guarda en claro: solo un hash para detectar duplicados.
     */
    public function submit(User $user, VerificationType $type, array $attributes = []): IdentityVerification
    {
        $provider = match ($type) {
            VerificationType::Email, VerificationType::Phone => VerificationProvider::Internal,
            VerificationType::Document, VerificationType::Liveness => VerificationProvider::forDocumentCountry(
                $attributes['document_country'] ?? $this->documentCountryFor($user)
            ),
            VerificationType::Address, VerificationType::Interview => VerificationProvider::Manual,
        };

        // El sexo del DNI (document_sex) solo viaja al proveedor, que lo pide para validar; no se guarda.
        $documentHash = isset($attributes['document_number'])
            ? hash('sha256', strtoupper($attributes['document_country'] ?? 'AR').'|'.preg_replace('/\W/', '', $attributes['document_number']))
            : null;

        if ($documentHash && $this->documentBelongsToSomeoneElse($user, $documentHash)) {
            throw new VerificationException('Ese documento ya está asociado a otra cuenta.');
        }

        // Email y teléfono se confirman con un código de seis dígitos que se envía al usuario.
        $result = null;
        if ($provider === VerificationProvider::Internal) {
            $code = (string) random_int(100000, 999999);
            $result = ['code_hash' => Hash::make($code), 'sent_to' => $type === VerificationType::Email ? $user->email : $user->phone];
            $this->deliverCode($user, $type, $code);
            if (! app()->isProduction()) {
                $result['demo_code'] = $code; // Solo fuera de producción, para poder probar el flujo.
            }
            // Un nuevo envío invalida el código anterior.
            $user->verifications()->where('type', $type)->where('status', VerificationStatus::Pending)->update(['status' => VerificationStatus::Expired]);
        }

        return $user->verifications()->create([
            'type' => $type,
            'provider' => $provider,
            'status' => VerificationStatus::Pending,
            'result' => $result,
            'expires_at' => $provider === VerificationProvider::Internal ? now()->addMinutes(30) : null,
            'document_country' => isset($attributes['document_country']) ? strtoupper($attributes['document_country']) : null,
            'document_type' => $attributes['document_type'] ?? null,
            'document_hash' => $documentHash,
            'provider_reference' => $attributes['provider_reference'] ?? null,
            'submitted_at' => now(),
        ]);
    }

    /** Confirma un código de email o teléfono. */
    public function confirmCode(User $user, VerificationType $type, string $code): IdentityVerification
    {
        if (! in_array($type, [VerificationType::Email, VerificationType::Phone], true)) {
            throw new VerificationException('Este tipo de verificación no usa código.');
        }

        $pending = $user->verifications()
            ->where('type', $type)
            ->where('status', VerificationStatus::Pending)
            ->latest('id')
            ->first();

        if (! $pending || ($pending->expires_at && $pending->expires_at->isPast())) {
            throw new VerificationException('El código venció. Pedí uno nuevo.');
        }
        if (! Hash::check(trim($code), $pending->result['code_hash'] ?? '')) {
            throw new VerificationException('El código no coincide.');
        }

        $pending->forceFill(['expires_at' => null])->save();
        $verification = $this->approve($pending, null, ['confirmed_at' => now()->toIso8601String()]);

        $user->forceFill([$type === VerificationType::Email ? 'email_verified_at' : 'phone_verified_at' => now()])->save();

        return $verification;
    }

    /**
     * El código de email sale por mail (Mandrill). El de teléfono queda en el
     * log hasta integrar un proveedor de SMS o WhatsApp.
     */
    private function deliverCode(User $user, VerificationType $type, string $code): void
    {
        if ($type === VerificationType::Email) {
            $user->notify(new VerificationCodeNotification($code));

            return;
        }

        Log::info('Código de verificación de teléfono generado', ['user' => $user->id, 'code' => app()->isProduction() ? '***' : $code]);
    }

    /** Aprueba una verificación (webhook del proveedor o decisión de un administrador). */
    public function approve(IdentityVerification $verification, ?User $reviewer = null, array $result = []): IdentityVerification
    {
        if ($verification->type->level() === VerificationLevel::Residence && ($reviewer === null || ! $reviewer->isAdmin())) {
            throw new VerificationException('El nivel 3 solo lo aprueba un administrador.');
        }

        return DB::transaction(function () use ($verification, $reviewer, $result) {
            $verification->forceFill([
                'status' => VerificationStatus::Approved,
                'result' => $result ?: $verification->result,
                'reviewed_by' => $reviewer?->getKey(),
                'reviewed_at' => now(),
            ])->save();

            $this->recalculateLevel($verification->user);

            return $verification;
        });
    }

    public function reject(IdentityVerification $verification, string $reason, ?User $reviewer = null): IdentityVerification
    {
        return DB::transaction(function () use ($verification, $reason, $reviewer) {
            $verification->forceFill([
                'status' => VerificationStatus::Rejected,
                'rejection_reason' => $reason,
                'reviewed_by' => $reviewer?->getKey(),
                'reviewed_at' => now(),
            ])->save();

            $this->recalculateLevel($verification->user);

            return $verification;
        });
    }

    /**
     * El nivel es acumulativo: se alcanza un nivel solo si todos los tipos
     * de ese nivel y de los anteriores están aprobados.
     */
    /**
     * Validación manual de un administrador: aprueba, con proveedor "manual",
     * las verificaciones que le falten a la cuenta hasta el nivel pedido.
     * Solo sube niveles; para frenar una cuenta está la suspensión.
     */
    public function grantLevelManually(User $user, VerificationLevel $level, User $admin, string $reason): VerificationLevel
    {
        if (! $admin->isAdmin()) {
            throw new VerificationException('Solo un administrador puede validar a mano.');
        }

        $required = collect(VerificationType::cases())
            ->reject(fn (VerificationType $type) => $type === VerificationType::Interview)
            ->filter(fn (VerificationType $type) => $type->level()->value <= $level->value);

        return DB::transaction(function () use ($user, $required, $admin, $reason) {
            $approved = $user->verifications()->where('status', VerificationStatus::Approved)->pluck('type')->map(fn ($t) => $t->value)->all();

            foreach ($required as $type) {
                if (in_array($type->value, $approved, true)) {
                    continue;
                }

                $user->verifications()->create([
                    'type' => $type,
                    'provider' => VerificationProvider::Manual,
                    'status' => VerificationStatus::Approved,
                    'result' => ['manual' => true, 'reason' => $reason],
                    'reviewed_by' => $admin->getKey(),
                    'submitted_at' => now(),
                    'reviewed_at' => now(),
                ]);

                if ($type === VerificationType::Email && ! $user->email_verified_at) {
                    $user->forceFill(['email_verified_at' => now()]);
                }
                if ($type === VerificationType::Phone && ! $user->phone_verified_at) {
                    $user->forceFill(['phone_verified_at' => now()]);
                }
            }

            return $this->recalculateLevel($user);
        });
    }

    public function recalculateLevel(User $user): VerificationLevel
    {
        $approved = $user->verifications()
            ->where('status', VerificationStatus::Approved)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->pluck('type')
            ->map(fn (VerificationType $t) => $t->value)
            ->all();

        $has = fn (VerificationType ...$types) => collect($types)->every(fn ($t) => in_array($t->value, $approved, true));

        $level = VerificationLevel::None;
        // El teléfono cuenta para el nivel 1 solo si la verificación por SMS está activa.
        $contactTypes = PlatformSettings::current()->smsVerification
            ? [VerificationType::Email, VerificationType::Phone]
            : [VerificationType::Email];

        if ($has(...$contactTypes)) {
            $level = VerificationLevel::Contact;
            if ($has(VerificationType::Document, VerificationType::Liveness)) {
                $level = VerificationLevel::Document;
                // Para nivel 3 alcanza el domicilio; la entrevista es a pedido del administrador.
                if ($has(VerificationType::Address)) {
                    $level = VerificationLevel::Residence;
                }
            }
        }

        $user->forceFill(['verification_level' => $level])->save();

        if ($level === VerificationLevel::Residence) {
            $this->activateHostIfEligible($user);
        }

        return $level;
    }

    /**
     * Al alcanzar el nivel 3, el perfil de anfitrión que estaba en revisión se
     * activa y sus experiencias con el contenido ya aprobado se publican solas.
     */
    private function activateHostIfEligible(User $user): void
    {
        $profile = $user->hostProfile;
        if (! $profile || $profile->status !== HostStatus::InReview) {
            return;
        }

        $profile->forceFill(['status' => HostStatus::Active])->save();
        // Uno por uno para que se disparen los avisos a interesados.
        $profile->experiences()
            ->where('status', ExperienceStatus::InReview)
            ->whereNotNull('approved_at')
            ->get()
            ->each(fn ($experience) => $experience->update(['status' => ExperienceStatus::Published, 'published_at' => now()]));
    }

    /** País del documento ya enviado; si no hay, la nacionalidad declarada. */
    private function documentCountryFor(User $user): string
    {
        return $user->verifications()
            ->where('type', VerificationType::Document)
            ->whereNotNull('document_country')
            ->latest('id')
            ->value('document_country') ?? $user->nationality_code ?? 'AR';
    }

    private function documentBelongsToSomeoneElse(User $user, string $hash): bool
    {
        return IdentityVerification::query()
            ->where('document_hash', $hash)
            ->where('user_id', '!=', $user->getKey())
            ->where('status', '!=', VerificationStatus::Rejected)
            ->exists();
    }
}
