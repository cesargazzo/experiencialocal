<?php

namespace App\Jobs;

use App\Enums\ExperienceStatus;
use App\Models\Experience;
use App\Models\User;
use App\Notifications\ExperienceMatchNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Avisa a quienes tienen intereses que coinciden con una experiencia
 * publicada o con fechas nuevas. A cada persona se le avisa como máximo
 * una vez por semana por la misma experiencia.
 */
class NotifyInterestedUsers implements ShouldQueue
{
    use Queueable;

    public const COOLDOWN_DAYS = 7;

    public function __construct(public Experience $experience, public string $reason = 'published') {}

    public function handle(): void
    {
        $experience = $this->experience->fresh(['host', 'province', 'category']);

        if (! $experience || $experience->status !== ExperienceStatus::Published) {
            return;
        }

        $nextDate = $experience->upcomingDates()->first();
        if (! $nextDate) {
            return;
        }

        $this->matchingUsers($experience)->chunkById(200, function ($users) use ($experience, $nextDate) {
            foreach ($users as $user) {
                $user->notify(new ExperienceMatchNotification($experience, $nextDate, $this->reason));

                DB::table('interest_notifications')->upsert(
                    ['user_id' => $user->id, 'experience_id' => $experience->id, 'notified_at' => now()],
                    ['user_id', 'experience_id'],
                    ['notified_at'],
                );
            }
        });
    }

    /**
     * Con categorías elegidas, tiene que coincidir la categoría; con provincias
     * elegidas, la provincia. Quien no eligió nada no recibe avisos.
     *
     * @return Builder<User>
     */
    private function matchingUsers(Experience $experience): Builder
    {
        return User::query()
            ->where('interest_alerts', true)
            ->whereNull('suspended_at')
            ->whereKeyNot($experience->host->user_id)
            ->where(fn ($q) => $q->has('interestedCategories')->orHas('interestedProvinces'))
            ->where(fn ($q) => $q->doesntHave('interestedCategories')->orWhereHas('interestedCategories', fn ($c) => $c->whereKey($experience->category_id)))
            ->where(fn ($q) => $q->doesntHave('interestedProvinces')->orWhereHas('interestedProvinces', fn ($p) => $p->whereKey($experience->province_id)))
            ->whereNotExists(fn ($q) => $q->selectRaw('1')
                ->from('interest_notifications')
                ->whereColumn('interest_notifications.user_id', 'users.id')
                ->where('interest_notifications.experience_id', $experience->id)
                ->where('interest_notifications.notified_at', '>', now()->subDays(self::COOLDOWN_DAYS)));
    }
}
