<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Validation\Rules\Password;

/**
 * Política de contraseñas vigente: los valores de config/tinku.php, pisados
 * por lo que un administrador haya guardado en la tabla settings.
 */
class PasswordPolicy
{
    public const SETTING_KEY = 'password_policy';

    /** Piso de seguridad: ninguna configuración puede bajar de acá. */
    public const ABSOLUTE_MIN_LENGTH = 8;

    public function __construct(
        public readonly int $minLength,
        public readonly bool $requireMixedCase,
        public readonly bool $requireNumbers,
        public readonly bool $requireSymbols,
        public readonly bool $checkUncompromised,
        public readonly int $maxLoginAttempts,
        public readonly int $lockoutMinutes,
    ) {}

    public static function current(): self
    {
        return self::fromArray(array_merge(config('tinku.password'), Setting::valueOf(self::SETTING_KEY) ?? []));
    }

    /**
     * @param  array{min_length?: int, require_mixed_case?: bool, require_numbers?: bool, require_symbols?: bool, check_uncompromised?: bool, max_login_attempts?: int, lockout_minutes?: int}  $values
     */
    public static function fromArray(array $values): self
    {
        return new self(
            minLength: max(self::ABSOLUTE_MIN_LENGTH, (int) ($values['min_length'] ?? self::ABSOLUTE_MIN_LENGTH)),
            requireMixedCase: (bool) ($values['require_mixed_case'] ?? false),
            requireNumbers: (bool) ($values['require_numbers'] ?? false),
            requireSymbols: (bool) ($values['require_symbols'] ?? false),
            checkUncompromised: (bool) ($values['check_uncompromised'] ?? false),
            maxLoginAttempts: max(1, (int) ($values['max_login_attempts'] ?? 5)),
            lockoutMinutes: max(1, (int) ($values['lockout_minutes'] ?? 15)),
        );
    }

    /**
     * @return array{min_length: int, require_mixed_case: bool, require_numbers: bool, require_symbols: bool, check_uncompromised: bool, max_login_attempts: int, lockout_minutes: int}
     */
    public function toArray(): array
    {
        return [
            'min_length' => $this->minLength,
            'require_mixed_case' => $this->requireMixedCase,
            'require_numbers' => $this->requireNumbers,
            'require_symbols' => $this->requireSymbols,
            'check_uncompromised' => $this->checkUncompromised,
            'max_login_attempts' => $this->maxLoginAttempts,
            'lockout_minutes' => $this->lockoutMinutes,
        ];
    }

    public function rule(): Password
    {
        $rule = Password::min($this->minLength)->letters();

        if ($this->requireMixedCase) {
            $rule->mixedCase();
        }
        if ($this->requireNumbers) {
            $rule->numbers();
        }
        if ($this->requireSymbols) {
            $rule->symbols();
        }
        if ($this->checkUncompromised) {
            $rule->uncompromised();
        }

        return $rule;
    }

    /**
     * Requisitos en palabras, para mostrar junto al campo.
     *
     * @return list<string>
     */
    public function requirements(): array
    {
        return array_values(array_filter([
            "Al menos {$this->minLength} caracteres",
            $this->requireMixedCase ? 'Mayúsculas y minúsculas' : 'Al menos una letra',
            $this->requireNumbers ? 'Al menos un número' : null,
            $this->requireSymbols ? 'Al menos un símbolo, como ! o #' : null,
            $this->checkUncompromised ? 'Que no aparezca en filtraciones conocidas' : null,
        ]));
    }
}
