<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('privacy:enforce-retention --force')
    ->dailyAt('02:00')
    ->when(fn () => config('privacy.retention.enabled'));

Schedule::command('identity:prune-staged-files')
    ->dailyAt('02:15')
    ->withoutOverlapping();
