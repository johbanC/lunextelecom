<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Requiere una entrada de cron en el servidor que llame a
// `php artisan schedule:run` cada minuto, p. ej.:
// * * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
Schedule::command('tickets:check-slas')->hourly()->withoutOverlapping();
