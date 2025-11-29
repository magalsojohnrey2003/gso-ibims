<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Alias command (optional): simple registration so you can run via artisan
Artisan::command('borrow:send-overdue-notifications:run', function () {
    $this->call('borrow:send-overdue-notifications');
})->purpose('Run the overdue notifications job immediately');

/*
 | Scheduling
 |
 | To run this daily at 08:00, add this to your `app/Console/Kernel.php` -> schedule() method:
 |
 |   protected function schedule(Schedule $schedule)
 |   {
 |       $schedule->command('borrow:send-overdue-notifications')->dailyAt('08:00');
 |   }
 |
 | Then ensure your server runs `php artisan schedule:run` every minute (cron / task scheduler).
 */
