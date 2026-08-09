<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BloodCareRefreshCommand extends Command
{
    protected $signature = 'bloodcare:refresh
        {--quick : Compatibility option; clear Laravel caches without any network work}
        {--basset : Optionally pre-cache non-core Backpack assets without deleting existing files}';

    protected $description = 'Safely clear BloodCare caches while preserving the local Backpack/Tabler asset bundle';

    public function handle(): int
    {
        $this->components->info('Clearing Laravel application caches...');

        if ($this->call('optimize:clear') !== self::SUCCESS) {
            $this->components->error('Laravel cache clearing failed.');

            return self::FAILURE;
        }

        $essentialAssets = [
            'vendor/bloodcare-backpack/css/tabler.min.css',
            'vendor/bloodcare-backpack/css/line-awesome.min.css',
            'vendor/bloodcare-backpack/js/jquery.min.js',
            'vendor/bloodcare-backpack/js/tabler.min.js',
        ];

        $missingAssets = array_values(array_filter(
            $essentialAssets,
            fn (string $asset): bool => ! is_file(public_path($asset))
        ));

        if ($missingAssets !== []) {
            $this->components->error('The local Backpack asset bundle is incomplete:');
            foreach ($missingAssets as $asset) {
                $this->line('  - public/'.$asset);
            }
            $this->newLine();
            $this->components->warn('Re-extract the current BloodCare patch before opening the admin workspace.');

            return self::FAILURE;
        }

        // Basset is now optional. Core Tabler/Backpack assets are local and are
        // deliberately never deleted by this command.
        if ($this->option('basset')) {
            $application = $this->getApplication();

            if ($application?->has('basset:cache')) {
                $this->components->info('Optionally pre-caching conditional Backpack assets...');

                if ($this->call('basset:cache') !== self::SUCCESS) {
                    $this->components->warn('Basset pre-caching did not finish. The core admin UI remains available from local assets.');
                }
            } else {
                $this->components->warn('The basset:cache command is not available.');
            }
        }

        $this->newLine();
        $this->components->info('BloodCare caches cleared. Local Backpack/Tabler assets were preserved.');

        return self::SUCCESS;
    }
}
