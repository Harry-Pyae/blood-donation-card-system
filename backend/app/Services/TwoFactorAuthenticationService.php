<?php

namespace App\Services;

use App\Models\User;

class TwoFactorAuthenticationService
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    private const PERIOD_SECONDS = 30;

    public function generateSecret(): string
    {
        return $this->base32Encode(random_bytes(20));
    }

    public function provisioningUri(User $user, string $secret): string
    {
        $issuer = trim((string) config('bloodcare.two_factor.issuer', 'BloodCare')) ?: 'BloodCare';
        $label = rawurlencode($issuer.':'.strtolower(trim((string) $user->email)));
        $query = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => 6,
            'period' => self::PERIOD_SECONDS,
        ], '', '&', PHP_QUERY_RFC3986);

        return "otpauth://totp/{$label}?{$query}";
    }

    /**
     * Return the accepted 30-second TOTP step, or null when the code is invalid
     * or has already been accepted for this account.
     */
    public function matchingStep(string $secret, string $code, ?int $lastUsedStep = null, ?int $timestamp = null): ?int
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';
        if (! preg_match('/^\d{6}$/', $code)) {
            return null;
        }

        $currentStep = intdiv($timestamp ?? time(), self::PERIOD_SECONDS);
        foreach ([-1, 0, 1] as $offset) {
            $step = $currentStep + $offset;
            if ($step < 0 || ($lastUsedStep !== null && $step <= $lastUsedStep)) {
                continue;
            }

            if (hash_equals($this->hotp($secret, $step), $code)) {
                return $step;
            }
        }

        return null;
    }

    /** Primarily useful for deterministic feature tests and local verification. */
    public function currentCode(string $secret, ?int $timestamp = null): string
    {
        return $this->hotp($secret, intdiv($timestamp ?? time(), self::PERIOD_SECONDS));
    }

    /** @return array<int, string> */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $raw = strtoupper(bin2hex(random_bytes(5)));
            $codes[] = substr($raw, 0, 5).'-'.substr($raw, 5);
        }

        return $codes;
    }

    /** @param array<int, string> $codes
     *  @return array<int, string>
     */
    public function hashRecoveryCodes(array $codes): array
    {
        return array_values(array_map(
            fn (string $code): string => hash('sha256', $this->normalizeRecoveryCode($code)),
            $codes
        ));
    }

    /**
     * Verify a current authenticator code or consume one recovery code.
     * Successful TOTP steps are persisted to prevent replay.
     */
    public function verifyAndConsume(User $user, string $code): bool
    {
        if (! $user->hasTwoFactorEnabled() || ! is_string($user->two_factor_secret)) {
            return false;
        }

        $step = $this->matchingStep(
            $user->two_factor_secret,
            $code,
            $user->two_factor_last_used_step !== null ? (int) $user->two_factor_last_used_step : null
        );

        if ($step !== null) {
            $user->forceFill(['two_factor_last_used_step' => $step])->save();

            return true;
        }

        $normalized = $this->normalizeRecoveryCode($code);
        if (! preg_match('/^[A-F0-9]{10}$/', $normalized)) {
            return false;
        }

        $candidate = hash('sha256', $normalized);
        $stored = is_array($user->two_factor_recovery_codes) ? $user->two_factor_recovery_codes : [];
        foreach ($stored as $index => $hash) {
            if (is_string($hash) && hash_equals($hash, $candidate)) {
                unset($stored[$index]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($stored)])->save();

                return true;
            }
        }

        return false;
    }

    private function hotp(string $secret, int $counter): string
    {
        $key = $this->base32Decode($secret);
        $high = intdiv($counter, 4294967296);
        $low = $counter % 4294967296;
        $digest = hash_hmac('sha1', pack('N2', $high, $low), $key, true);
        $offset = ord($digest[19]) & 0x0f;
        $binary = ((ord($digest[$offset]) & 0x7f) << 24)
            | ((ord($digest[$offset + 1]) & 0xff) << 16)
            | ((ord($digest[$offset + 2]) & 0xff) << 8)
            | (ord($digest[$offset + 3]) & 0xff);

        return str_pad((string) ($binary % 1000000), 6, '0', STR_PAD_LEFT);
    }

    private function base32Encode(string $bytes): string
    {
        $bits = '';
        foreach (str_split($bytes) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';
        foreach (str_split($bits, 5) as $chunk) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $encoded .= self::BASE32_ALPHABET[bindec($chunk)];
        }

        return $encoded;
    }

    private function base32Decode(string $encoded): string
    {
        $encoded = strtoupper(preg_replace('/[^A-Z2-7]/i', '', $encoded) ?? '');
        $bits = '';
        foreach (str_split($encoded) as $character) {
            $position = strpos(self::BASE32_ALPHABET, $character);
            if ($position === false) {
                return '';
            }
            $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }

        $decoded = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $decoded .= chr(bindec($chunk));
            }
        }

        return $decoded;
    }

    private function normalizeRecoveryCode(string $code): string
    {
        return strtoupper(preg_replace('/[^A-F0-9]/i', '', trim($code)) ?? '');
    }
}
