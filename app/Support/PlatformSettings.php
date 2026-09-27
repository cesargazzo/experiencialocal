<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Interruptores de la plataforma que un administrador cambia desde el panel.
 * Los valores por defecto están en config/tinku.php.
 */
class PlatformSettings
{
    public const SETTING_KEY = 'platform';

    public function __construct(public readonly bool $smsVerification) {}

    public static function current(): self
    {
        $values = array_merge(config('tinku.features'), Setting::valueOf(self::SETTING_KEY) ?? []);

        return new self(smsVerification: (bool) ($values['sms_verification'] ?? false));
    }

    /**
     * @return array{sms_verification: bool}
     */
    public function toArray(): array
    {
        return ['sms_verification' => $this->smsVerification];
    }
}
