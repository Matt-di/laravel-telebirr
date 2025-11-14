<?php

namespace Telebirr\LaravelTelebirr;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Telebirr\LaravelTelebirr\Contracts\MerchantResolverInterface;
use Telebirr\LaravelTelebirr\Contracts\WebhookHandlerInterface;
use Telebirr\LaravelTelebirr\Services\MerchantResolver;
use Telebirr\LaravelTelebirr\Services\WebhookHandler;
use Telebirr\LaravelTelebirr\Drivers\DriverManager;

class TelebirrServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerRoutes();
        $this->registerMigrations();
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'telebirr');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/Config/telebirr.php', 'telebirr');

        $this->registerBindings();
        $this->registerCommands();
    }

    /**
     * Register package bindings.
     *
     * @return void
     */
    protected function registerBindings(): void
    {
        $this->app->singleton(DriverManager::class, function (Application $app) {
            return new DriverManager($app);
        });

        $this->app->singleton(MerchantResolverInterface::class, function (Application $app) {
            $resolverClass = config('telebirr.merchant.resolver', MerchantResolver::class);
            return new $resolverClass();
        });

        $this->app->singleton(WebhookHandlerInterface::class, function (Application $app) {
            $handlerClass = config('telebirr.webhook.handler', WebhookHandler::class);
            return new $handlerClass();
        });

        $this->app->singleton('telebirr', function (Application $app) {
            return new TelebirrManager($app);
        });
    }

    /**
     * Register artisan commands.
     *
     * @return void
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\InstallCommand::class,
                Console\Commands\SetupWebhookCommand::class,
                Console\Commands\TestConnectionCommand::class,
            ]);
        }
    }

    /**
     * Register package routes.
     *
     * @return void
     */
    protected function registerRoutes(): void
    {
        if ($this->app['config']->get('telebirr.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/Routes/api.php', 'telebirr');
        }
    }

    /**
     * Register package migrations.
     *
     * @return void
     */
    protected function registerMigrations(): void
    {
        if ($this->app['config']->get('telebirr.migrations.enabled', true)) {
            $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        }
    }

    /**
     * Register publishing resources.
     *
     * @return void
     */
    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/Config/telebirr.php' => config_path('telebirr.php'),
            ], 'telebirr-config');

            $this->publishes([
                __DIR__.'/../Database/Migrations' => database_path('migrations'),
            ], 'telebirr-migrations');

            $this->publishes([
                __DIR__.'/../Routes/api.php' => base_path('routes/telebirr.php'),
            ], 'telebirr-routes');

            $this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/telebirr'),
            ], 'telebirr-lang');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return ['telebirr'];
    }
}
