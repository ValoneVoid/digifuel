<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto-retensi mingguan: bersihkan log audit yang sudah berusia lebih dari 180 hari (6 bulan)
Schedule::command('audit:purge --days=180 --force')->weekly();

