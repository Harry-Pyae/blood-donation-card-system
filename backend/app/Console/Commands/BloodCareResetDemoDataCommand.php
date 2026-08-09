<?php

namespace App\Console\Commands;

use Database\Seeders\DemoDataSeeder;
use Database\Seeders\FefoDemoStockSeeder;
use Illuminate\Console\Command;

class BloodCareResetDemoDataCommand extends Command
{
    protected $signature = 'bloodcare:demo-data:reset
        {--force : Skip the confirmation prompt}
        {--with-fefo : Also load the optional standalone FEFO stress-test stock}';

    protected $description = 'Remove old BloodCare demo records and load the current deterministic demo dataset';

    public function handle(): int
    {
        if (! app()->environment('local', 'testing')) {
            $this->components->error('Demo data can be reset only in the local or testing environment.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm(
            'Remove the old/current BloodCare demo records and restore the current demo dataset?',
            false,
        )) {
            $this->components->info('Nothing was changed.');

            return self::SUCCESS;
        }

        if ($this->call('bloodcare:demo-data:clear', ['--force' => true]) !== self::SUCCESS) {
            $this->components->error('Demo cleanup failed; the new dataset was not loaded.');

            return self::FAILURE;
        }

        if ($this->call('db:seed', ['--class' => DemoDataSeeder::class, '--force' => true]) !== self::SUCCESS) {
            $this->components->error('Demo seeding failed after cleanup.');

            return self::FAILURE;
        }

        if ($this->option('with-fefo')) {
            if ($this->call('db:seed', ['--class' => FefoDemoStockSeeder::class, '--force' => true]) !== self::SUCCESS) {
                $this->components->error('The current dataset loaded, but the optional FEFO stress-test stock failed.');

                return self::FAILURE;
            }
        }

        $this->newLine();
        $this->components->info('BloodCare demo data reset complete.');

        return self::SUCCESS;
    }
}
