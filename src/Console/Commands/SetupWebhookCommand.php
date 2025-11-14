<?php

namespace Telebirr\LaravelTelebirr\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Setup and test webhook endpoint for Telebirr.
 */
class SetupWebhookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telebirr:setup-webhook
                            {--url= : Custom webhook URL}
                            {--test : Send a test webhook}
                            {--verbose : Show detailed output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup and test the Telebirr webhook endpoint';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔗 Setting up Telebirr webhook endpoint');
        $this->newLine();

        $webhookUrl = $this->option('url') ?: $this->getDefaultWebhookUrl();

        $this->info("Webhook URL: {$webhookUrl}");

        // Validate webhook URL is reachable
        $this->validateWebhookUrl($webhookUrl);

        if ($this->option('test')) {
            $this->sendTestWebhook($webhookUrl);
        }

        $this->newLine();
        $this->displayWebhookInfo($webhookUrl);

        return self::SUCCESS;
    }

    /**
     * Get the default webhook URL.
     *
     * @return string
     */
    protected function getDefaultWebhookUrl(): string
    {
        $webhookPath = config('telebirr.webhook.path', '/api/telebirr/webhook');
        return url($webhookPath);
    }

    /**
     * Validate that the webhook URL is reachable.
     *
     * @param string $url
     * @return void
     */
    protected function validateWebhookUrl(string $url): void
    {
        $this->info('🔍 Validating webhook endpoint...');

        try {
            $response = Http::timeout(10)->get($url);

            if ($response->successful()) {
                $this->info('✅ Webhook endpoint is reachable');
                if ($this->option('verbose')) {
                    $this->line("Response: {$response->status()} - {$response->body()}");
                }
            } else {
                $this->warn("⚠️ Webhook endpoint returned status {$response->status()}");
                $this->warn('This may be expected if authentication is required.');
            }
        } catch (\Exception $e) {
            $this->error('❌ Webhook endpoint is not reachable: ' . $e->getMessage());
            $this->newLine();
            $this->error('Please ensure:');
            $this->error('1. Laravel application is running');
            $this->error('2. Webhook routes are published and enabled');
            $this->error('3. No firewall/proxy is blocking the request');

            return;
        }
    }

    /**
     * Send a test webhook to verify the endpoint.
     *
     * @param string $url
     * @return void
     */
    protected function sendTestWebhook(string $url): void
    {
        $this->newLine();
        $this->info('🧪 Sending test webhook...');

        $testPayload = [
            'merch_order_id' => 'TEST_' . time(),
            'trade_status' => 'Completed',
            'total_amount' => '100.00',
            'trade_no' => 'TEST123456',
            'timestamp' => now()->timestamp,
        ];

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'Telebirr-Test/1.0',
                ])
                ->post($url, $testPayload);

            if ($response->successful()) {
                $responseData = $response->json();

                if (isset($responseData['code']) && $responseData['code'] === '0') {
                    $this->info('✅ Test webhook processed successfully');

                    if ($this->option('verbose')) {
                        $this->line('Response: ' . json_encode($responseData, JSON_PRETTY_PRINT));
                    }
                } else {
                    $this->warn('⚠️ Test webhook processed but returned unexpected response');
                    $this->line('Response: ' . $response->body());
                }
            } else {
                $this->error("❌ Test webhook failed with status {$response->status()}");
                $this->line('Response: ' . $response->body());
            }
        } catch (\Exception $e) {
            $this->error('❌ Test webhook failed: ' . $e->getMessage());
        }
    }

    /**
     * Display webhook setup information.
     *
     * @param string $url
     * @return void
     */
    protected function displayWebhookInfo(string $url): void
    {
        $this->newLine();
        $this->info('📝 Webhook Setup Information:');
        $this->newLine();

        $this->bullet("Endpoint: {$url}");
        $this->bullet('Method: POST');
        $this->bullet('Content-Type: application/json');

        if (config('telebirr.webhook.secret')) {
            $this->newLine();
            $this->warn('⚠️ Webhook signature verification is enabled');
            $this->bullet('Ensure webhooks include X-Telebirr-Signature and X-Telebirr-Timestamp headers');
        }

        $this->newLine();
        $this->info('📚 Expected Payload Structure:');
        $testPayload = [
            'merch_order_id' => 'TXN_123',
            'trade_status' => 'Completed',
            'total_amount' => '100.00',
            'trade_no' => 'TRADE123',
            'timestamp' => time(),
        ];

        $this->line(json_encode($testPayload, JSON_PRETTY_PRINT));
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
