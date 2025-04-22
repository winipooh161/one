<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class OptimizeCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'optimize:cache {--all : Clear all caches and recreate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize all cache settings for better performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('all')) {
            $this->clear();
        }
        
        $this->info('Creating application cache...');

        $this->call('config:cache');
        $this->call('route:cache');
        $this->call('view:cache');
        
        if ($this->confirm('Do you want to cache events as well?')) {
            $this->call('event:cache');
        }
        
        $this->info('Application cache created successfully!');
        $this->warn('Please restart your web server to apply changes.');
    }
    
    /**
     * Clear all application caches
     */
    protected function clear()
    {
        $this->info('Clearing application cache...');
        
        $this->call('cache:clear');
        $this->call('config:clear');
        $this->call('route:clear');
        $this->call('view:clear');
        $this->call('event:clear');

        $this->info('Application cache cleared!');
    }
}
