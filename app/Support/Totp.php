<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Códigos de un solo uso por tiempo (TOTP, RFC 6238): los que muestran Google
 * Authenticator, Microsoft Authenticator, 1Password, Authy, etc. Seis dígitos
 * que cambian cada 30 segundos.
 */
class Totp
{
    public const PERIOD = 30;

    public const DIGITS = 6;

    /** Se acepta el código anterior y el siguiente, por si el reloj del celular no está exacto. */
    public const WINDOW = 1;

    private const BASE32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    public static function code(string $secret, int $step): string
    {
        $hash = hash_hmac('sha1', pack('J', $step), self::base32Decode($secret), true);
        $offset = ord($hash[19]) & 0x0F;
        $value = ((ord($hash[$offset]) & 0x7F) << 24) | (ord($hash[$offset + 1]) << 16) | (ord($hash[$offset + 2]) << 8) | ord($hash[$offset + 3]);

        return str_pad((string) ($value % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    public static function currentStep(?int $timestamp = null): int
    {
        return intdiv($timestamp ?? time(), self::PERIOD);
    }

    /**
     * Devuelve el intervalo del código si es válido y posterior al último usado; si no, null.
     */
    public static function verify(string $secret, string $code, ?int $lastUsedStep = null, ?int $timestamp = null): ?int
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';
        if (! preg_match('/^\d{'.self::DIGITS.'}$/', $code)) {
            return null;
        }

        $now = self::currentStep($timestamp);
        for ($step = $now - self::WINDOW; $step <= $now + self::WINDOW; $step++) {
            if (($lastUsedStep === null || $step > $lastUsedStep) && hash_equals(self::code($secret, $step), $code)) {
                return $step;
            }
        }

        return null;
    }

    public static function provisioningUri(string $secret, string $account, string $issuer): string
    {
        return 'otpauth://totp/'.rawurlencode($issuer.':'.$account).'?'.http_build_query([
            'secret' => $secret, 'issuer' => $issuer, 'algorithm' => 'SHA1', 'digits' => self::DIGITS, 'period' => self::PERIOD,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /** Código QR en SVG, generado en el servidor: el secreto no pasa por ningún servicio externo. */
    public static function qrSvg(string $uri, int $size = 200): string
    {
        return (new Writer(new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd)))->writeString($uri);
    }

    public static function base32Encode(string $data): string
    {
        $bits = '';
        foreach (str_split($data) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        return implode('', array_map(fn (string $chunk) => self::BASE32[bindec(str_pad($chunk, 5, '0'))], str_split($bits, 5)));
    }

    public static function base32Decode(string $data): string
    {
        $bits = '';
        foreach (str_split(strtoupper(rtrim($data, '='))) as $char) {
            $position = strpos(self::BASE32, $char);
            if ($position !== false) {
                $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
            }
        }

        return implode('', array_map(fn (string $byte) => chr(bindec($byte)), array_filter(str_split($bits, 8), fn (string $byte) => strlen($byte) === 8)));
    }
}
