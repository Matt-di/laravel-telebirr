<?php

namespace Telebirr\LaravelTelebirr\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Install the Telebirr package with all necessary setup.
 */
class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telebirr:install
                            {--mode=single : Installation mode (single or multi)}
                            {--publish-config : Publish configuration file}
                            {--publish-migrations : Publish migrations}
                            {--publish-routes : Publish routes}
                            {--run-migrations : Run database migrations}
                            {--force : Force overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and setup the Telebirr package';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🚀 Installing Telebirr Payment Gateway');
        $this->newLine();

        $mode = $this->option('mode');
        $force = $this->option('force');

        // Set the mode in config if provided
        if ($mode) {
            $this->setMode($mode);
        }

        // Publish configuration
        if ($this->option('publish-config') || !$this->hasConfig()) {
            $this->publishConfig($force);
        }

        // Publish migrations if multi-merchant mode
        if (($this->option('publish-migrations') || $mode === 'multi') && !$this->hasMigrations()) {
            $this->publishMigrations($force);
        }

        // Publish routes if requested
        if ($this->option('publish-routes')) {
            $this->publishRoutes($force);
        }

        // Run migrations if requested
        if ($this->option('run-migrations')) {
            $this->runMigrations();
        }

        $this->newLine();
        $this->info('✅ Telebirr package installed successfully!');
        $this->displayNextSteps($mode);

        return self::SUCCESS;
    }

    /**
     * Set the Telebirr mode.
     *
     * @param string $mode
     * @return void
     */
    protected function setMode(string $mode): void
    {
        if (!in_array($mode, ['single', 'multi'])) {
            $this->error("Invalid mode: {$mode}. Must be 'single' or 'multi'");
            return;
        }

        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $envContent = file_get_contents($envPath);

            // Check if TELEBIRR_MODE already exists
            if (preg_match('/^TELEBIRR_MODE=/m', $envContent)) {
                $envContent = preg_replace('/^TELEBIRR_MODE=.*/m', "TELEBIRR_MODE={$mode}", $envContent);
            } else {
                $envContent .= "\nTELEBIRR_MODE={$mode}\n";
            }

            file_put_contents($envPath, $envContent);
            $this->info("✅ Set Telebirr mode to: {$mode}");
        } else {
            $this->warn('.env file not found. Please manually set TELEBIRR_MODE in your environment.');
        }
    }

    /**
     * Publish the configuration file.
     *
     * @param bool $force
     * @return void
     */
    protected function publishConfig(bool $force = false): void
    {
        $this->info('📋 Publishing configuration file...');

        $params = ['--provider' => 'Telebirr\LaravelTelebirr\TelebirrServiceProvider'];
        if ($force) {
            $params['--force'] = true;
        }

        try {
            Artisan::call('vendor:publish', array_merge($params, [
                '--tag' => 'telebirr-config'
            ]));

            $this->info('✅ Configuration file published to config/telebirr.php');
        } catch (\Exception $e) {
            $this->error('Failed to publish config: ' . $e->getMessage());
        }
    }

    /**
     * Publish the migrations.
     *
     * @param bool $force
     * @return void
     */
    protected function publishMigrations(bool $force = false): void
    {
        $this->info('🗄️ Publishing migrations...');

        $params = ['--provider' => 'Telebirr\LaravelTelebirr\TelebirrServiceProvider'];
        if ($force) {
            $params['--force'] = true;
        }

        try {
            Artisan::call('vendor:publish', array_merge($params, [
                '--tag' => 'telebirr-migrations'
            ]));

            $this->info('✅ Migrations published to database/migrations/');
        } catch (\Exception $e) {
            $this->error('Failed to publish migrations: ' . $e->getMessage());
        }
    }

    /**
     * Publish the routes.
     *
     * @param bool $force
     * @return void
     */
    protected function publishRoutes(bool $force = false): void
    {
        $this->info('🛣️ Publishing routes...');

        $params = ['--provider' => 'Telebirr\LaravelTelebirr\TelebirrServiceProvider'];
        if ($force) {
            $params['--force'] = true;
        }

        try {
            Artisan::call('vendor:publish', array_merge($params, [
                '--tag' => 'telebirr-routes'
            ]));

            $this->info('✅ Routes published');
        } catch (\Exception $e) {
            $this->error('Failed to publish routes: ' . $e->getMessage());
        }
    }

    /**
     * Run database migrations.
     *
     * @return void
     */
    protected function runMigrations(): void
    {
        $this->info('🔄 Running migrations...');

        try {
            Artisan::call('migrate');

            $this->info('✅ Migrations executed successfully');
        } catch (\Exception $e) {
            $this->error('Failed to run migrations: ' . $e->getMessage());
        }
    }

    /**
     * Check if config file already exists.
     *
     * @return bool
     */
    protected function hasConfig(): bool
    {
        return file_exists(config_path('telebirr.php'));
    }

    /**
     * Check if migrations are already published.
     *
     * @return bool
     */
    protected function hasMigrations(): bool
    {
        $migrations = glob(database_path('migrations/*create_telebirr_merchants_table.php'));
        return !empty($migrations);
    }

    /**
     * Display next steps after installation.
     *
     * @param string|null $mode
     * @return void
     */
    protected function displayNextSteps(?string $mode): void
    {
        $this->newLine();
        $this->info('🎯 Next Steps:');

        if (!$this->hasConfig() && !$this->option('publish-config')) {
            $this->bullet('Configure your Telebirr credentials in .env file');
            $this->bullet('Run: php artisan vendor:publish --tag=telebirr-config');
        }

        if ($mode === 'multi' && !$this->hasMigrations() && !$this->option('publish-migrations') && !$this->option('run-migrations')) {
            $this->bullet('For multi-merchant mode, run: php artisan vendor:publish --tag=telebirr-migrations');
            $this->bullet('Then run: php artisan migrate');
        }

        $this->bullet('Test connection: php artisan telebirr:test-connection');
        $this->bullet('Setup webhook: php artisan telebirr:setup-webhook');

        $this->newLine();
        $this->info('📚 Documentation: https://github.com/matirezzo/laravel-telebirr');
        $this->info('🆘 Support: https://github.com/matirezzo/laravel-telebirr/issues');
    }

    /**
     * Display a bullet point.
     *
     * @param string $text
     * @return void
     */
    protected function bullet(string $text): void
    {
        $this->line('  • ' . $text);
    }
}
