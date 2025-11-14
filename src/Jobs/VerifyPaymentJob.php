<?php

namespace Telebirr\LaravelTelebirr\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Telebirr\LaravelTelebirr\Events\PaymentVerified;
use Telebirr\LaravelTelebirr\Services\TelebirrService;

/**
 * Job to verify Telebirr payment asynchronously.
 *
 * This job handles webhook payment verification with retry logic
 * similar to the user's existing VerifyTelebirrPaymentJob.
 */
class VerifyPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public int $timeout;

    /**
     * The retry delay schedule in seconds.
     *
     * @var array
     */
    protected array $retrySchedule;

    /**
     * The transaction reference to verify.
     *
     * @var string
     */
    protected string $transactionRef;

    /**
     * The webhook payload data.
     *
     * @var array
     */
    protected array $webhookData;

    /**
     * The client IP address.
     *
     * @var string|null
     */
    protected ?string $clientIp;

    /**
     * Create a new job instance.
     *
     * @param string $transactionRef
     * @param array $webhookData
     * @param string|null $clientIp
     */
    public function __construct(string $transactionRef, array $webhookData = [], ?string $clientIp = null)
    {
        $this->transactionRef = $transactionRef;
        $this->webhookData = $webhookData;
        $this->clientIp = $clientIp;

        // Configure job properties from config
        $this->tries = config('telebirr.queue.verify_payment.tries', 5);
        $this->timeout = config('telebirr.queue.verify_payment.timeout', 120);
        $this->retrySchedule = config('telebirr.queue.verify_payment.retry_schedule', [5, 5, 5, 5, 5]);

        $this->queue = config('telebirr.queue.verify_payment.queue', 'telebirr');
        $this->connection = config('telebirr.queue.verify_payment.connection', 'database');
    }

    /**
     * Execute the job.
     *
     * @param TelebirrService $telebirrService
     * @return void
     */
    public function handle(TelebirrService $telebirrService): void
    {
        // Log job start
        Log::info('Starting Telebirr payment verification job', [
            'transaction_ref' => $this->transactionRef,
            'attempt' => $this->attempts(),
            'client_ip' => $this->clientIp,
        ]);

        // Verify payment with Telebirr API
        $result = $telebirrService->verifyPayment($this->transactionRef);

        if (!$result) {
            Log::warning('Telebirr payment verification failed', [
                'transaction_ref' => $this->transactionRef,
                'attempt' => $this->attempts(),
            ]);

            // Release job with backoff delay
            $this->release($this->getNextRetryDelay());
            return;
        }

        $paymentStatus = $result['order_status'] ?? null;
        $amount = $result['total_amount'] ?? null;

        Log::info('Telebirr payment verification result', [
            'transaction_ref' => $this->transactionRef,
            'status' => $paymentStatus,
            'amount' => $amount,
        ]);

        if ($paymentStatus === 'PAY_SUCCESS') {
            // Dispatch payment verified event
            PaymentVerified::dispatch($this->transactionRef, $result, $this->webhookData);

            Log::info('Telebirr payment successfully verified', [
                'transaction_ref' => $this->transactionRef,
                'amount' => $amount,
                'webhook_data' => $this->webhookData,
            ]);

            return;
        }

        if ($paymentStatus === 'PAY_FAILED') {
            Log::info('Telebirr payment failed (PAY_FAILED)', [
                'transaction_ref' => $this->transactionRef,
            ]);
            return;
        }

        // Unknown or pending status - retry
        Log::warning('Telebirr payment unknown status, retrying', [
            'transaction_ref' => $this->transactionRef,
            'status' => $paymentStatus,
            'attempt' => $this->attempts(),
        ]);

        $this->release($this->getNextRetryDelay());
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Telebirr payment verification job failed permanently', [
            'transaction_ref' => $this->transactionRef,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
            'webhook_data' => $this->webhookData,
        ]);

        // Could dispatch a failure event here if needed
        // PaymentFailed::dispatch($this->transactionRef, $exception);
    }

    /**
     * Get the next retry delay.
     *
     * @return int
     */
    protected function getNextRetryDelay(): int
    {
        $attempt = $this->attempts();
        return $this->retrySchedule[$attempt - 1] ?? end($this->retrySchedule);
    }
}
