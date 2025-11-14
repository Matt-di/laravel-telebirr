<?php

namespace Telebirr\LaravelTelebirr\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentInitiated
{
    use Dispatchable, SerializesModels;

    /**
     * The invoice/transaction ID.
     *
     * @var string
     */
    public string $invoiceId;

    /**
     * The order data.
     *
     * @var array
     */
    public array $orderData;

    /**
     * The merchant context.
     *
     * @var array
     */
    public array $context;

    /**
     * Create a new event instance.
     *
     * @param string $invoiceId
     * @param array $orderData
     * @param array $context
     */
    public function __construct(string $invoiceId, array $orderData, array $context = [])
    {
        $this->invoiceId = $invoiceId;
        $this->orderData = $orderData;
        $this->context = $context;
    }
}
