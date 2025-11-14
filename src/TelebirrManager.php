<?php

namespace Telebirr\LaravelTelebirr;

use Illuminate\Contracts\Foundation\Application;
use Telebirr\LaravelTelebirr\Drivers\DriverInterface;
use Telebirr\LaravelTelebirr\Services\TelebirrService;
use Illuminate\Http\Request;

class TelebirrManager
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * Create a new Telebirr manager instance.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Initiate a payment with Telebirr.
     *
     * @param array $data
     * @param array $context
     * @return string
     */
    public function initiatePayment(array $data, array $context = []): string
    {
        $service = $this->getService($context);
        return $service->createPreorder($data);
    }

    /**
     * Verify a payment with Telebirr.
     *
     * @param string $transactionId
     * @param array $context
     * @return array
     */
    public function verifyPayment(string $transactionId, array $context = []): array
    {
        $service = $this->getService($context);
        return $service->verifyPayment($transactionId) ?? [];
    }

    /**
     * Query an order status with Telebirr.
     *
     * @param string $orderId
     * @param array $context
     * @return array
     */
    public function queryOrder(string $orderId, array $context = []): array
    {
        $service = $this->getService($context);
        return $service->queryOrder($orderId) ?? [];
    }

    /**
     * Handle a webhook from Telebirr.
     *
     * @param Request $request
     * @return bool
     */
    public function handleWebhook(Request $request): bool
    {
        $webhookHandler = $this->app->make(\Telebirr\LaravelTelebirr\Contracts\WebhookHandlerInterface::class);
        $webhookHandler->handle($request);
        return true;
    }

    /**
     * Get auth token from Telebirr.
     *
     * @param string $accessToken
     * @param array $context
     * @return array
     */
    public function getAuthToken(string $accessToken, array $context = []): array
    {
        $service = $this->getService($context);
        return $service->getAuthToken($accessToken) ?? [];
    }

    /**
     * Get a driver instance.
     *
     * @param string|null $name
     * @return DriverInterface
     */
    public function driver(?string $name = null): DriverInterface
    {
        return $this->app->make(\Telebirr\LaravelTelebirr\Drivers\DriverManager::class)->driver($name);
    }

    /**
     * Get the Telebirr service instance.
     *
     * @param array $context
     * @return TelebirrService
     */
    protected function getService(array $context = []): TelebirrService
    {
        return $this->app->make(TelebirrService::class, [
            'context' => $context,
        ]);
    }
}
