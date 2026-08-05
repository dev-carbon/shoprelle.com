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

// The database and the customers' screenshots live on a single VPS disk; the
// nightly archive is what makes a bad migration or a mistake recoverable.
// Before the prune above, so the archive still holds what it deletes.
Schedule::command('shoprelle:backup')->dailyAt('02:30');
