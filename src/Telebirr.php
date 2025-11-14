<?php

namespace Telebirr\LaravelTelebirr;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string initiatePayment(array $data, array $context = [])
 * @method static array verifyPayment(string $transactionId, array $context = [])
 * @method static array queryOrder(string $orderId, array $context = [])
 * @method static bool handleWebhook(\Illuminate\Http\Request $request)
 * @method static array getAuthToken(string $accessToken, array $context = [])
 * @method static \Telebirr\LaravelTelebirr\Drivers\DriverInterface driver(string $name = null)
 */
class Telebirr extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'telebirr';
    }

    /**
     * Get the Telebirr instance from the container.
     *
     * @return \Telebirr\LaravelTelebirr\TelebirrManager
     */
    public static function getFacadeRoot()
    {
        return static::resolveFacadeInstance(static::getFacadeAccessor());
    }
}
