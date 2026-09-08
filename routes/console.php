<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Kirim email pemberitahuan pelamar yang diantrekan (status 'queued') oleh HRIS / admin.
Schedule::command('notifications:dispatch')->everyMinute()->withoutOverlapping();
