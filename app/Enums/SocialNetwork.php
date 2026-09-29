<?php

namespace App\Enums;

/**
 * Redes y web que cada persona puede declarar en su perfil. Se guarda siempre la
 * dirección armada por Tinku: aunque escriban "@usuario" o peguen cualquier cosa,
 * el enlace apunta solo al sitio de esa red (nada de javascript: ni dominios falsos).
 */
enum SocialNetwork: string
{
    case Instagram = 'instagram';
    case Facebook = 'facebook';
    case TikTok = 'tiktok';
    case X = 'x';
    case LinkedIn = 'linkedin';
    case YouTube = 'youtube';
    case Website = 'web';

    public function label(): string
    {
        return match ($this) {
            self::Instagram => 'Instagram',
            self::Facebook => 'Facebook',
            self::TikTok => 'TikTok',
            self::X => 'X (Twitter)',
            self::LinkedIn => 'LinkedIn',
            self::YouTube => 'YouTube',
            self::Website => 'Web',
        };
    }

    public function placeholder(): string
    {
        return match ($this) {
            self::Instagram, self::TikTok, self::X => '@tuusuario',
            self::Facebook => 'facebook.com/tuusuario',
            self::LinkedIn => 'linkedin.com/in/tuusuario',
            self::YouTube => '@tucanal',
            self::Website => 'tusitio.com.ar',
        };
    }

    /** Arma la dirección oficial a partir de lo que escribió la persona, o null si no es válida. */
    public function normalize(string $input): ?string
    {
        $input = trim($input);
        if ($input === '' || mb_strlen($input) > 200) {
            return null;
        }

        if ($this === self::Website) {
            return self::website($input);
        }

        $handle = $this->handleFrom($input);
        if ($handle === null || ! preg_match($this->handlePattern(), $handle)) {
            return null;
        }

        return match ($this) {
            self::Instagram => "https://www.instagram.com/{$handle}",
            self::Facebook => "https://www.facebook.com/{$handle}",
            self::TikTok => "https://www.tiktok.com/@{$handle}",
            self::X => "https://x.com/{$handle}",
            self::LinkedIn => "https://www.linkedin.com/in/{$handle}",
            self::YouTube => "https://www.youtube.com/@{$handle}",
        };
    }

    /** Cómo se muestra el enlace: @usuario en las redes, el dominio en la web. */
    public function display(string $url): string
    {
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');

        return match ($this) {
            self::Website => preg_replace('/^www\./', '', (string) parse_url($url, PHP_URL_HOST)).($path !== '' ? '/'.$path : ''),
            self::Facebook => $path,
            self::LinkedIn => preg_replace('#^in/#', '', $path),
            default => '@'.ltrim(preg_replace('#^@#', '', $path), '@'),
        };
    }

    /** @return list<string> Dominios propios de la red. */
    private function domains(): array
    {
        return match ($this) {
            self::Instagram => ['instagram.com'],
            self::Facebook => ['facebook.com', 'fb.com'],
            self::TikTok => ['tiktok.com'],
            self::X => ['x.com', 'twitter.com'],
            self::LinkedIn => ['linkedin.com'],
            self::YouTube => ['youtube.com'],
            self::Website => [],
        };
    }

    private function handlePattern(): string
    {
        return match ($this) {
            self::Instagram => '/^[A-Za-z0-9._]{1,30}$/',
            self::Facebook => '/^[A-Za-z0-9.]{5,50}$/',
            self::TikTok => '/^[A-Za-z0-9._]{2,24}$/',
            self::X => '/^[A-Za-z0-9_]{1,15}$/',
            self::LinkedIn => '/^[A-Za-z0-9\-_]{3,100}$/',
            self::YouTube => '/^[A-Za-z0-9._\-]{3,30}$/',
            self::Website => '/^$/',
        };
    }

    /** Acepta "@usuario", "usuario" o la dirección completa de esa misma red. */
    private function handleFrom(string $input): ?string
    {
        if (! str_contains($input, '/') && ! str_contains($input, '.com')) {
            return ltrim($input, '@');
        }

        $url = preg_match('#^https?://#i', $input) ? $input : 'https://'.$input;
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = preg_replace('/^(www\.|m\.|mobile\.)/', '', $host);
        if ($this === self::LinkedIn) {
            // Subdominio por país: ar.linkedin.com, es.linkedin.com…
            $host = preg_replace('/^[a-z]{2}\./', '', $host);
        }
        if (! in_array($host, $this->domains(), true)) {
            return null;
        }

        $segments = array_values(array_filter(explode('/', (string) parse_url($url, PHP_URL_PATH))));
        if ($this === self::LinkedIn) {
            return ($segments[0] ?? null) === 'in' ? ($segments[1] ?? null) : null;
        }

        return isset($segments[0]) ? ltrim($segments[0], '@') : null;
    }

    private static function website(string $input): ?string
    {
        $url = preg_match('#^[a-z][a-z0-9+.\-]*:#i', $input) ? $input : 'https://'.$input;
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if (! in_array($scheme, ['http', 'https'], true) || ! filter_var($url, FILTER_VALIDATE_URL)
            || ! preg_match('/^([a-z0-9-]+\.)+[a-z]{2,}$/', $host) || parse_url($url, PHP_URL_USER) !== null) {
            return null;
        }

        return $url;
    }
}
