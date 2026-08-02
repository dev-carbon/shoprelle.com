<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Screenshots staged by conversations that were never confirmed would otherwise
// stay on disk indefinitely.
Schedule::command('shoprelle:prune-pending-attachments')->dailyAt('03:00');
