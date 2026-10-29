<?php
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
Artisan::command('act:health', function () { $this->info('Arewa Civic Tracker CLI ready.'); });
Schedule::command('act:review-deadlines')->dailyAt('08:00')->timezone('Africa/Lagos')->withoutOverlapping();
Schedule::command('act:secure-backup')->dailyAt('02:15')->timezone('Africa/Lagos')->withoutOverlapping();
Schedule::command('act:privacy-maintenance')->dailyAt('03:15')->timezone('Africa/Lagos')->withoutOverlapping();

Schedule::command('act:copy-backup-offsite')->dailyAt('02:55')->timezone('Africa/Lagos')
    ->when(fn()=>filled(config('act.offsite_backup_dir')))->withoutOverlapping();
