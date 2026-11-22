<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

\Illuminate\Support\Facades\Schedule::command('privacy:enforce-retention --force')
    ->dailyAt('02:00')
    ->when(fn() => config('privacy.retention.enabled'));
