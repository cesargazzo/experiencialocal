<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    /** Una conversación privada la ven solo sus dos participantes. */
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->isParticipant($user);
    }

    public function send(User $user, Conversation $conversation): bool
    {
        return $conversation->isParticipant($user) && ! $user->isSuspended();
    }
}
