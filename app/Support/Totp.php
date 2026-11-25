<?php

namespace App\Support;

class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    public static function code(string $secret, ?int $timestamp = null, int $digits = 6, int $period = 30): string
    {
        $key = self::base32Decode($secret);
        $counter = intdiv($timestamp ?? time(), $period);
        $binary = pack('N2', 0, $counter);
        $hash = hash_hmac('sha1', $binary, $key, true);
        $offset = ord($hash[19]) & 0x0f;

        $value = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % (10 ** $digits);

        return str_pad((string) $value, $digits, '0', STR_PAD_LEFT);
    }

    public static function verify(string $secret, string $code, int $window = 1, ?int $timestamp = null): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';

        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $now = $timestamp ?? time();

        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::code($secret, $now + ($i * 30)), $code)) {
                return true;
            }
        }

        return false;
    }

    public static function otpauthUrl(string $issuer, string $account, string $secret): string
    {
        $label = rawurlencode($issuer . ':' . $account);

        return 'otpauth://totp/' . $label
            . '?secret=' . $secret
            . '&issuer=' . rawurlencode($issuer)
            . '&digits=6&period=30';
    }

    public static function recoveryCodes(int $count = 8): array
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $raw = self::base32Encode(random_bytes(5));

            $codes[] = substr($raw, 0, 4) . '-' . substr($raw, 4, 4);
        }

        return $codes;
    }

    public static function base32Encode(string $data): string
    {
        $bits = '';

        foreach (str_split($data) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $output = '';

        foreach (str_split($bits, 5) as $chunk) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $output .= self::ALPHABET[bindec($chunk)];
        }

        return $output;
    }

    public static function base32Decode(string $encoded): string
    {
        $encoded = strtoupper(preg_replace('/[^A-Z2-7]/', '', $encoded) ?? '');
        $bits = '';

        foreach (str_split($encoded) as $char) {
            $position = strpos(self::ALPHABET, $char);

            if ($position === false) {
                continue;
            }

            $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }

        $output = '';

        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $output .= chr(bindec($byte));
            }
        }

        return $output;
    }
}
