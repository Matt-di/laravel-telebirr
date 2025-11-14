<?php

namespace Telebirr\LaravelTelebirr\Services;

use Illuminate\Support\Facades\Log;
use Telebirr\LaravelTelebirr\Exceptions\TelebirrException;

/**
 * Telebirr Signature Service
 *
 * Handles RSA signing and verification for Telebirr API requests.
 * Uses PSS padding as required by Telebirr specifications.
 */
class SignatureService
{
    /**
     * Generate a nonce string for requests.
     *
     * @return string
     */
    public function generateNonce(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Generate a timestamp for requests (YYYYMMDDHHMM format).
     *
     * @return string
     */
    public function generateTimestamp(): string
    {
        return now()->format('YmdHi'); // YYYYMMDDHHMM (12 characters) to meet Telebirr's 13 char limit
    }

    /**
     * Sign a request using RSA with PSS padding.
     *
     * @param array $data
     * @param string $privateKey
     * @return string
     *
     * @throws TelebirrException
     */
    public function signRequest(array $data, string $privateKey): string
    {
        // Use the exact same signing logic from the working PHP example
        $exclude_fields = ["sign", "sign_type", "header", "refund_info", "openType", "raw_request"];
        $dataFiltered = $data;
        ksort($dataFiltered);
        $stringApplet = '';

        foreach ($dataFiltered as $key => $values) {
            if (in_array($key, $exclude_fields)) {
                continue;
            }

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

        $sortedString = $this->sortedString($stringApplet);

        if (config('telebirr.logging.enabled', true)) {
            Log::info('Telebirr signature data', [
                'original_data' => $data,
                'string_to_sign' => $sortedString,
                'exclude_fields' => $exclude_fields
            ]);
        }

        return $this->signWithRSA($sortedString, $privateKey);
    }

    /**
     * Sort string exactly like the working PHP example.
     */
    private function sortedString(string $stringApplet): string
    {
        $stringExplode = '';
        $sortedArray = explode("&", $stringApplet);
        sort($sortedArray);
        foreach ($sortedArray as $x => $x_value) {
            if ($stringExplode == '') {
                $stringExplode = $x_value;
            } else {
                $stringExplode = $stringExplode . '&' . $x_value;
            }
        }

        return $stringExplode;
    }

    /**
     * Sign with RSA using phpseclib 3.x (PSS padding).
     */
    private function signWithRSA(string $data, string $privateKey): string
    {
        try {
            // Ensure private key has proper format
            if (!str_contains($privateKey, '-----BEGIN')) {
                $privateKey = "-----BEGIN PRIVATE KEY-----\n" . $privateKey . "\n-----END PRIVATE KEY-----";
            }

            // Use phpseclib 3.x API for PSS padding
            $rsa = \phpseclib3\Crypt\RSA::loadPrivateKey($privateKey);

            // Set PSS padding (this is what Telebirr expects)
            $rsa = $rsa->withPadding(\phpseclib3\Crypt\RSA::SIGNATURE_PSS);
            $rsa = $rsa->withHash('sha256');
            $rsa = $rsa->withMGFHash('sha256');

            $signature = $rsa->sign($data);

            if (config('telebirr.logging.enabled', true)) {
                Log::info('Signature generated with phpseclib RSA (PSS padding)');
            }

            return base64_encode($signature);
        } catch (\Exception $e) {
            if (config('telebirr.logging.enabled', true)) {
                Log::warning('phpseclib RSA signing failed, falling back to OpenSSL', [
                    'error' => $e->getMessage()
                ]);
            }

            // Fallback to OpenSSL
            return $this->signWithOpenSSL($data, $privateKey);
        }
    }

    /**
     * Fallback to OpenSSL for signing.
     */
    private function signWithOpenSSL(string $data, string $privateKey): string
    {
        // Ensure private key has proper format
        if (!str_contains($privateKey, '-----BEGIN')) {
            $privateKey = "-----BEGIN PRIVATE KEY-----\n" . $privateKey . "\n-----END PRIVATE KEY-----";
        }

        $privateKeyResource = openssl_pkey_get_private($privateKey);

        if (!$privateKeyResource) {
            $error = openssl_error_string();
            if (config('telebirr.logging.enabled', true)) {
                Log::error('Failed to load RSA private key for signing', [
                    'error' => $error,
                    'key_length' => strlen($privateKey),
                    'key_preview' => substr($privateKey, 0, 50) . '...'
                ]);
            }
            throw new TelebirrException('Invalid RSA private key: ' . $error);
        }

        // Use standard OpenSSL SHA256
        $result = openssl_sign($data, $signature, $privateKeyResource, OPENSSL_ALGO_SHA256);
        if ($result) {
            if (config('telebirr.logging.enabled', true)) {
                Log::info('Signature generated with OpenSSL');
            }
            return base64_encode($signature);
        }

        $error = openssl_error_string();
        if (config('telebirr.logging.enabled', true)) {
            Log::error('Failed to sign request with OpenSSL', ['error' => $error]);
        }
        throw new TelebirrException('Failed to sign request: ' . $error);
    }

    /**
     * Verify a signature with RSA.
     *
     * @param string $data
     * @param string $signature
     * @param string $publicKey
     * @return bool
     */
    public function verifySignature(string $data, string $signature, string $publicKey): bool
    {
        try {
            // Ensure public key has proper format
            if (!str_contains($publicKey, '-----BEGIN')) {
                $publicKey = "-----BEGIN PUBLIC KEY-----\n" . $publicKey . "\n-----END PUBLIC KEY-----";
            }

            // Use phpseclib 3.x for verification
            $rsa = \phpseclib3\Crypt\RSA::loadPublicKey($publicKey);
            $rsa = $rsa->withPadding(\phpseclib3\Crypt\RSA::SIGNATURE_PSS);
            $rsa = $rsa->withHash('sha256');
            $rsa = $rsa->withMGFHash('sha256');

            return $rsa->verify($data, base64_decode($signature));
        } catch (\Exception $e) {
            if (config('telebirr.logging.enabled', true)) {
                Log::warning('phpseclib verification failed, falling back to OpenSSL', [
                    'error' => $e->getMessage()
                ]);
            }

            // Fallback to OpenSSL
            $publicKeyResource = openssl_pkey_get_public($publicKey);
            if (!$publicKeyResource) {
                if (config('telebirr.logging.enabled', true)) {
                    Log::error('Failed to load RSA public key for verification');
                }
                return false;
            }

            $result = openssl_verify($data, base64_decode($signature), $publicKeyResource, OPENSSL_ALGO_SHA256);
            return $result === 1;
        }
    }

    /**
     * Extract public key from private key.
     *
     * @param string $privateKey
     * @return string|null
     */
    public function extractPublicKeyFromPrivateKey(string $privateKey): ?string
    {
        try {
            // Ensure private key has proper format
            if (!str_contains($privateKey, '-----BEGIN')) {
                $privateKey = "-----BEGIN PRIVATE KEY-----\n" . $privateKey . "\n-----END PRIVATE KEY-----";
            }

            // Load the private key
            $privateKeyResource = openssl_pkey_get_private($privateKey);

            if (!$privateKeyResource) {
                if (config('telebirr.logging.enabled', true)) {
                    Log::error('Failed to load private key for public key extraction: ' . openssl_error_string());
                }
                return null;
            }

            // Extract public key details
            $publicKeyDetails = openssl_pkey_get_details($privateKeyResource);

            if (!$publicKeyDetails || !isset($publicKeyDetails['key'])) {
                if (config('telebirr.logging.enabled', true)) {
                    Log::error('Failed to extract public key details: ' . openssl_error_string());
                }
                return null;
            }

            return $publicKeyDetails['key'];
        } catch (\Exception $e) {
            if (config('telebirr.logging.enabled', true)) {
                Log::error('Exception extracting public key from private key: ' . $e->getMessage());
            }
            return null;
        }
    }
}
