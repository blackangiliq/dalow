<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// تذكير الزبائن بإعادة شراء أدويتهم: كل يوم الساعة 10 صباحاً بتوقيت بغداد
\Illuminate\Support\Facades\Schedule::command('care:remind')->dailyAt('10:00')->timezone('Asia/Baghdad');
