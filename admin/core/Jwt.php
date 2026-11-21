<?php

/**
 * Tiny HS256 JSON Web Token implementation.
 * The signing secret comes from JWT_SECRET in .env.
 */
class Jwt
{
    private static function secret(): string
    {
        $secret = JWT_SECRET;
        if (strlen($secret) < 32) {
            throw new RuntimeException('JWT_SECRET in admin/.env must be at least 32 characters.');
        }
        return $secret;
    }

    private static function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function unb64(string $data): string|false
    {
        return base64_decode(strtr($data, '-_', '+/'), true);
    }

    public static function encode(array $claims, int $ttlSeconds): string
    {
        $now = time();
        $payload = $claims + ['iat' => $now, 'exp' => $now + $ttlSeconds];

        $head = self::b64(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $body = self::b64(json_encode($payload));
        $sig = self::b64(hash_hmac('sha256', "$head.$body", self::secret(), true));

        return "$head.$body.$sig";
    }

    /** Returns the claims, or null if the token is malformed, tampered with or expired. */
    public static function decode(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$head, $body, $sig] = $parts;
        $expected = self::b64(hash_hmac('sha256', "$head.$body", self::secret(), true));
        if (!hash_equals($expected, $sig)) {
            return null;
        }

        $header = self::unb64($head);
        $header = $header === false ? null : json_decode($header, true);
        if (!is_array($header) || ($header['alg'] ?? '') !== 'HS256') {
            return null;
        }

        $json = self::unb64($body);
        $claims = $json === false ? null : json_decode($json, true);
        if (!is_array($claims) || ($claims['exp'] ?? 0) < time()) {
            return null;
        }

        return $claims;
    }
}
