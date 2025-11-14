<?php

namespace Telebirr\LaravelTelebirr\Drivers;

use Illuminate\Contracts\Foundation\Application;
use Telebirr\LaravelTelebirr\Drivers\SingleMerchantDriver;
use Telebirr\LaravelTelebirr\Drivers\MultiMerchantDriver;
use InvalidArgumentException;

class DriverManager
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * The registered drivers.
     *
     * @var array
     */
    protected array $drivers = [];

    /**
     * Create a new driver manager instance.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get a driver instance.
     *
     * @param string|null $name
     * @return \Telebirr\LaravelTelebirr\Drivers\DriverInterface
     */
    public function driver(?string $name = null): DriverInterface
    {
        $name = $name ?: config('telebirr.mode', 'single');

        if (!isset($this->drivers[$name])) {
            $this->drivers[$name] = $this->createDriver($name);
        }

        return $this->drivers[$name];
    }

    /**
     * Create a new driver instance.
     *
     * @param string $name
     * @return \Telebirr\LaravelTelebirr\Drivers\DriverInterface
     *
     * @throws InvalidArgumentException
     */
    protected function createDriver(string $name): DriverInterface
    {
        return match ($name) {
            'single' => $this->app->make(SingleMerchantDriver::class),
            'multi' => $this->app->make(MultiMerchantDriver::class),
            default => throw new InvalidArgumentException("Driver [{$name}] is not supported."),
        };
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return config('telebirr.mode', 'single');
    }

    /**
     * Set the default driver name.
     *
     * @param string $name
     * @return void
     */
    public function setDefaultDriver(string $name): void
    {
        config(['telebirr.mode' => $name]);
    }

    /**
     * Register a custom driver creator.
     *
     * @param string $name
     * @param callable $callback
     * @return void
     */
    public function extend(string $name, callable $callback): void
    {
        $this->drivers[$name] = $callback($this->app);
    }
}
