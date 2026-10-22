<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('exchange-rates:fetch')
    ->dailyAt('16:30')
    ->timezone('Europe/Berlin')
    ->withoutOverlapping()
    ->onOneServer();
