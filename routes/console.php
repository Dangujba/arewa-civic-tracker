<?php
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
Artisan::command('act:health', function () { $this->info('Arewa Civic Tracker CLI ready.'); });

Schedule::command('act:review-deadlines')->dailyAt('08:00')->timezone('Africa/Lagos')->withoutOverlapping();
