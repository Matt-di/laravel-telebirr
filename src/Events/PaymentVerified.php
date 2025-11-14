<?php

namespace Telebirr\LaravelTelebirr\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentVerified
{
    use Dispatchable, SerializesModels;

    /**
     * The transaction reference.
     *
     * @var string
     */
    public string $transactionRef;

    /**
     * The verification result from Telebirr.
     *
     * @var array
     */
    public array $result;

    /**
     * The original webhook data.
     *
     * @var array
     */
    public array $webhookData;

    /**
     * Create a new event instance.
     *
     * @param string $transactionRef
     * @param array $result
     * @param array $webhookData
     */
    public function __construct(string $transactionRef, array $result, array $webhookData = [])
    {
        $this->transactionRef = $transactionRef;
        $this->result = $result;
        $this->webhookData = $webhookData;
    }
}
