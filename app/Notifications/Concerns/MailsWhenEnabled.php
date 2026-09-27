<?php

namespace App\Notifications\Concerns;

use App\Support\PlatformSettings;

/**
 * Los avisos siempre quedan en la campanita. El mail se manda solo si está
 * prendido en Admin > Configuración (apagado mientras Tinku es una demo).
 */
trait MailsWhenEnabled
{
    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return PlatformSettings::current()->notificationEmails ? ['mail', 'database'] : ['database'];
    }

    /**
     * El aviso en Tinku se guarda al momento; el mail sale por la cola.
     *
     * @return array<string, string>
     */
    public function viaConnections(): array
    {
        return ['database' => 'sync'];
    }
}
