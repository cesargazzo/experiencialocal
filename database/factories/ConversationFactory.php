<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Experience;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    public function definition(): array
    {
        $experience = Experience::factory()->create();

        return [
            'experience_id' => $experience->id,
            'guest_id' => User::factory(),
            'host_user_id' => $experience->host->user_id,
        ];
    }
}
