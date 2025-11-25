<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearLog extends Command
{
    protected $signature = 'log:clear';
    protected $description = 'Clear the laravel.log file';

    public function handle()
    {
        $file = storage_path('logs/laravel.log');
        
        if (file_exists($file)) {
            file_put_contents($file, '');
            $this->info('Log file cleared!');
        } else {
            $this->error('Log file not found.');
        }
    }
}