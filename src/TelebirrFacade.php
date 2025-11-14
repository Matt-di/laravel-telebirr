<?php

namespace Telebirr\LaravelTelebirr;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array initiatePayment(array $data, array $context = [])
 * @method static array|null verifyPayment(string $transactionId, array $context = [])
 * @method static array|null queryOrder(string $orderId, array $context = [])
 * @method static array|null getAuthToken(string $accessToken, array $context = [])
 * @method static mixed handleWebhook(\Illuminate\Http\Request $request)
 * @method static \Telebirr\LaravelTelebirr\Drivers\DriverInterface driver(string $driver = null)
 *
 * @see \Telebirr\LaravelTelebirr\TelebirrManager
 */
class TelebirrFacade extends Facade
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
}
