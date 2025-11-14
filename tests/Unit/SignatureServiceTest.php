<?php

namespace Telebirr\LaravelTelebirr\Tests\Unit;

use Telebirr\LaravelTelebirr\Services\SignatureService;
use Telebirr\LaravelTelebirr\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class SignatureServiceTest extends TestCase
{
    private SignatureService $signatureService;
    private $rsaKeyPair;

    protected function setUp(): void
    {
        parent::setUp();

        $this->signatureService = new SignatureService();

        // Generate test RSA key pair
        $this->rsaKeyPair = $this->generateTestRsaKeyPair();
    }

    protected function tearDown(): void
    {
        // Clean up any config changes
        config([
            'telebirr.logging.enabled' => false,
            'telebirr.logging.sensitive_data' => false,
        ]);

        parent::tearDown();
    }

    public function test_generates_nonce()
    {
        $nonce = $this->signatureService->generateNonce();

        $this->assertIsString($nonce);
        $this->assertEquals(32, strlen($nonce)); // 16 bytes as hex = 32 characters
    }

    public function test_generates_unique_nonces()
    {
        $nonce1 = $this->signatureService->generateNonce();
        $nonce2 = $this->signatureService->generateNonce();

        $this->assertNotEquals($nonce1, $nonce2);
    }

    public function test_generates_timestamp()
    {
        $timestamp = $this->signatureService->generateTimestamp();

        $this->assertIsString($timestamp);
        $this->assertEquals(12, strlen($timestamp)); // YYYYMMDDHHMM format
        $this->assertMatchesRegularExpression('/^\d{12}$/', $timestamp);
    }

    public function test_sign_request_success()
    {
        $data = [
            'method' => 'payment.preorder',
            'timestamp' => '202312011200',
            'biz_content' => [
                'merch_order_id' => 'TEST123',
                'total_amount' => '100.00',
            ]
        ];

        $signature = $this->signatureService->signRequest($data, $this->rsaKeyPair['private']);

        $this->assertIsString($signature);
        $this->assertNotEmpty($signature);

        // Test that signature can be decoded from base64
        $decoded = base64_decode($signature, true);
        $this->assertNotFalse($decoded);
    }

    public function test_verify_signature_success()
    {
        $data = [
            'method' => 'payment.preorder',
            'timestamp' => '202312011200',
            'biz_content' => [
                'merch_order_id' => 'TEST123',
                'total_amount' => '100.00',
            ]
        ];

        $signature = $this->signatureService->signRequest($data, $this->rsaKeyPair['private']);

        $isValid = $this->signatureService->verifySignature(
            $this->createStringToSign($data),
            $signature,
            $this->rsaKeyPair['public']
        );

        $this->assertTrue($isValid);
    }

    public function test_verify_signature_invalid()
    {
        $data = [
            'method' => 'payment.preorder',
            'timestamp' => '202312011200',
        ];

        // Sign with correct data
        $signature = $this->signatureService->signRequest($data, $this->rsaKeyPair['private']);

        // Try to verify with different data
        $modifiedData = [
            'method' => 'payment.query', // Different method
            'timestamp' => '202312011200',
        ];

        $isValid = $this->signatureService->verifySignature(
            $this->createStringToSign($modifiedData),
            $signature,
            $this->rsaKeyPair['public']
        );

        $this->assertFalse($isValid);
    }

    public function test_extract_public_key_from_private_key()
    {
        $publicKey = $this->signatureService->extractPublicKeyFromPrivateKey($this->rsaKeyPair['private']);

        $this->assertIsString($publicKey);
        $this->assertNotEmpty($publicKey);
        $this->assertStringContainsString('-----BEGIN PUBLIC KEY-----', $publicKey);
    }

    public function test_extract_public_key_with_invalid_private_key()
    {
        $result = $this->signatureService->extractPublicKeyFromPrivateKey('invalid-key');

        $this->assertNull($result);
    }

    public function test_logging_disabled_in_tests()
    {
        config(['telebirr.logging.enabled' => false]);

        // This should not throw any logging errors
        $signature = $this->signatureService->signRequest(
            ['method' => 'test'],
            $this->rsaKeyPair['private']
        );

        $this->assertIsString($signature);
    }

    private function generateTestRsaKeyPair(): array
    {
        // Generate RSA key pair for testing
        $privateKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        // Extract private key
        openssl_pkey_export($privateKey, $privateKeyPem);

        // Extract public key
        $publicKeyDetails = openssl_pkey_get_details($privateKey);
        $publicKeyPem = $publicKeyDetails['key'];

        return [
            'private' => $privateKeyPem,
            'public' => $publicKeyPem,
        ];
    }

    private function createStringToSign(array $data): string
    {
        // Recreate the string to sign logic from SignatureService
        ksort($data);
        $stringApplet = '';

        foreach ($data as $key => $values) {
            if ($key == "biz_content") {
                foreach ($values as $value => $single_value) {
                    if ($stringApplet == '') {
                        $stringApplet = $value . '=' . $single_value;
                    } else {
                        $stringApplet = $stringApplet . '&' . $value . '=' . $single_value;
                    }
                }
            } else {
                if ($stringApplet == '') {
                    $stringApplet = $key . '=' . $values;
                } else {
                    $stringApplet = $stringApplet . '&' . $key . '=' . $values;
                }
            }
        }

        // Sort and create final string (simplified version)
        $sortedArray = explode("&", $stringApplet);
        sort($sortedArray);

        return implode('&', $sortedArray);
    }
}
