<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Self-healing tiling: re-queue any image that lost its IIIF tiles (job dropped,
// worker was down, etc.). Idempotent — TileImage skips already-tiled and orphan
// images, so this is safe to run unattended. Requires `schedule:run` in cron.
Schedule::command('images:tile-status --dispatch')
    ->hourly()
    ->withoutOverlapping();
