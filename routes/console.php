<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Email pemberitahuan pelamar: HRIS memanggil POST /api/integrations/hris/notifications;
// email dikirim lewat Mail ShouldQueue -> queue:work. Tak ada scheduled task.
