<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\TermsVersionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Una versión de los términos y condiciones. Mientras es borrador se edita;
 * publicada queda fija y su texto se guarda con una huella.
 */
#[Fillable(['version', 'title', 'body', 'changes_summary', 'requires_reacceptance', 'created_by'])]
class TermsVersion extends Model
{
    use Auditable;

    /** @use HasFactory<TermsVersionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'requires_reacceptance' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function acceptances(): HasMany
    {
        return $this->hasMany(TermsAcceptance::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }

    /** La versión vigente: la última publicada. */
    public static function current(): ?self
    {
        return self::query()->published()->latest('published_at')->latest('id')->first();
    }

    /** La última versión que hay que haber aceptado para seguir usando Tinku. */
    public static function required(): ?self
    {
        return self::query()->published()->where('requires_reacceptance', true)->latest('published_at')->latest('id')->first();
    }

    public function publish(User $admin): void
    {
        $this->forceFill([
            'published_at' => now(),
            'published_by' => $admin->id,
            'body_hash' => hash('sha256', $this->body),
        ])->save();
    }
}
