<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use LogicException;

/**
 * Quién aceptó qué versión, cuándo y desde dónde. Es un registro: no se edita.
 */
#[Fillable(['user_id', 'terms_version_id', 'accepted_at', 'ip', 'user_agent', 'context'])]
class TermsAcceptance extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['accepted_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Una aceptación de términos no se modifica.'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(TermsVersion::class, 'terms_version_id');
    }

    public static function record(User $user, TermsVersion $version, Request $request, string $context): self
    {
        return self::query()->firstOrCreate(
            ['user_id' => $user->id, 'terms_version_id' => $version->id],
            [
                'accepted_at' => now(),
                'ip' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
                'context' => $context,
            ],
        );
    }
}
