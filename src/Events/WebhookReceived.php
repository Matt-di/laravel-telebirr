<?php

namespace Telebirr\LaravelTelebirr\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebhookReceived
{
    use Dispatchable, SerializesModels;

    /**
     * The webhook payload.
     *
     * @var array
     */
    public array $payload;

    /**
     * Create a new event instance.
     *
     * @param array $payload
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }
}
