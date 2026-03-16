<?php

namespace App\Support;

/**
 * TOTP (Time-based One-Time Password) implementation for 2FA.
 * RFC 6238 compliant — no external packages required.
 */
class TwoFactorAuth
{
    private const PERIOD = 30;
    private const DIGITS = 6;
    private const ALGORITHM = 'sha1';

    /**
     * Generate a random base32-encoded secret key.
     */
    public static function generateSecret(int $length = 16): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        $bytes = random_bytes($length);
        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[ord($bytes[$i]) % 32];
        }
        return $secret;
    }

    /**
     * Get the current TOTP code for the given secret.
     */
    public static function getCode(string $secret, ?int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();
        $timeSlice = intdiv($timestamp, self::PERIOD);
        return self::generateOtp($secret, $timeSlice);
    }

    /**
     * Verify a TOTP code with a window of ±1 period.
     */
    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $timestamp = time();
        $timeSlice = intdiv($timestamp, self::PERIOD);

        for ($i = -$window; $i <= $window; $i++) {
            $expected = self::generateOtp($secret, $timeSlice + $i);
            if (hash_equals($expected, str_pad($code, self::DIGITS, '0', STR_PAD_LEFT))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generate a provisioning URI for QR code scanners.
     */
    public static function getQrUri(string $secret, string $email, string $issuer = 'Constraal'): string
    {
        $label = rawurlencode($issuer) . ':' . rawurlencode($email);
        return 'otpauth://totp/' . $label
            . '?secret=' . $secret
            . '&issuer=' . rawurlencode($issuer)
            . '&algorithm=SHA1'
            . '&digits=' . self::DIGITS
            . '&period=' . self::PERIOD;
    }

    /**
     * Generate recovery codes.
     */
    public static function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4))) . '-' . strtoupper(bin2hex(random_bytes(4)));
        }
        return $codes;
    }

    /**
     * Core HOTP generation (RFC 4226).
     */
    private static function generateOtp(string $secret, int $counter): string
    {
        $key = self::base32Decode($secret);
        $packed = pack('N*', 0, $counter);
        $hash = hash_hmac(self::ALGORITHM, $packed, $key, true);

        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $binary = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        );

        return str_pad($binary % pow(10, self::DIGITS), self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Decode base32 string.
     */
    private static function base32Decode(string $input): string
    {
        $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $input = strtoupper(rtrim($input, '='));
        $buffer = 0;
        $bitsLeft = 0;
        $output = '';

        for ($i = 0, $len = strlen($input); $i < $len; $i++) {
            $val = strpos($map, $input[$i]);
            if ($val === false) {
                continue;
            }
            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $output;
    }
}
