<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// AI Agent 配額等待任務恢復排程 - 每15分鐘執行一次，處理所有已到期的waiting_quota任務
Schedule::command('ai:agent recover')->everyFifteenMinutes();
