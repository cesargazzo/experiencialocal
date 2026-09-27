<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Borra eventos de seguridad viejos y otros modelos con poda automática.
Schedule::command('model:prune')->daily()->at('03:15');
