<?php

namespace Ichavezrg\HeyBancoClient;

/**
 * Quita de lo que el cliente escribe en el log todo lo que sirve para
 * autenticarse: el `client_secret`, los tokens que entrega Hey (`access_token`,
 * `id_token`, `refresh_token`), las cabeceras `Authorization` y `Cookie` y
 * cualquier campo cuyo nombre diga secret, password o token.
 *
 * Funciona por nombre de campo y no por valor: se reconoce un secreto por cómo
 * se llama, sin importar si viaja en un arreglo, en un JSON o en un cuerpo
 * `application/x-www-form-urlencoded`. Los cuerpos de las operaciones de la API
 * ya viajan cifrados (JWE) y no se tocan.
 */
final class LogSanitizer
{
    public const REDACTED = '[REDACTED]';

    /**
     * Opciones de la petición de Guzzle que sí se registran. El resto (`cert`,
     * `ssl_key`, `auth`, `curl`, `handler`...) puede llevar rutas o contraseñas y
     * nunca se escribe en el log.
     */
    private const LOGGED_REQUEST_OPTIONS = ['headers', 'query', 'json', 'form_params', 'body'];

    private const SENSITIVE_FRAGMENTS = [
        'secret',
        'password',
        'passwd',
        'passphrase',
        'privatekey',
        'authorization',
        'cookie',
        'apikey',
    ];

    /**
     * Las opciones de una petición, solo las que se registran y sin secretos.
     *
     * @param array<array-key, mixed> $options
     * @return array<string, mixed>
     */
    public static function request(array $options): array
    {
        $logged = [];

        foreach (self::LOGGED_REQUEST_OPTIONS as $name) {
            if (!array_key_exists($name, $options)) {
                continue;
            }

            $logged[$name] = $name === 'body'
                ? self::body($options[$name])
                : self::value($options[$name]);
        }

        return $logged;
    }

    /**
     * Cabeceras HTTP, de una petición o de una respuesta.
     *
     * @param array<array-key, mixed> $headers
     * @return array<array-key, mixed>
     */
    public static function headers(array $headers): array
    {
        return self::sanitizeArray($headers);
    }

    /**
     * El cuerpo de una petición o de una respuesta. Lo que no es texto (un
     * stream, un recurso) se reemplaza por una marca: no se lee.
     */
    public static function body(mixed $body): mixed
    {
        if (is_string($body)) {
            return self::text($body);
        }

        if (is_array($body)) {
            return self::sanitizeArray($body);
        }

        if (is_scalar($body) || $body === null) {
            return $body;
        }

        return '[' . (is_object($body) ? get_class($body) : gettype($body)) . ']';
    }

    /**
     * Cualquier valor que se vaya a registrar, de forma recursiva.
     */
    public static function value(mixed $value): mixed
    {
        if (is_array($value)) {
            return self::sanitizeArray($value);
        }

        if (is_string($value)) {
            return self::text($value);
        }

        return $value;
    }

    /**
     * El contexto de un registro.
     *
     * @param array<array-key, mixed> $context
     * @return array<array-key, mixed>
     */
    public static function context(array $context): array
    {
        return self::sanitizeArray($context);
    }

    /**
     * Un texto: si es un JSON se sanea por campos; si no, se tapan los Bearer y
     * los pares `campo=valor` de nombre sensible.
     */
    public static function text(string $text): string
    {
        $trimmed = ltrim($text);

        if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
            $decoded = json_decode($text, true);

            if (is_array($decoded)) {
                $encoded = json_encode(
                    self::sanitizeArray($decoded),
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR
                );

                return $encoded === false ? self::REDACTED : $encoded;
            }
        }

        return self::scrub($text);
    }

    public static function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower((string) preg_replace('/[^a-z0-9]/i', '', $key));

        if ($normalized === '') {
            return false;
        }

        if (str_ends_with($normalized, 'token')) {
            return true;
        }

        foreach (self::SENSITIVE_FRAGMENTS as $fragment) {
            if (str_contains($normalized, $fragment)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<array-key, mixed> $data
     * @return array<array-key, mixed>
     */
    private static function sanitizeArray(array $data): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            $clean[$key] = is_string($key) && self::isSensitiveKey($key)
                ? self::REDACTED
                : self::value($value);
        }

        return $clean;
    }

    private static function scrub(string $text): string
    {
        $text = (string) preg_replace('/\bBearer\s+[A-Za-z0-9\-._~+\/]+=*/i', 'Bearer ' . self::REDACTED, $text);

        return (string) preg_replace_callback(
            '/\b([a-z0-9_-]+)=([^&\s]+)/i',
            static fn (array $match): string => self::isSensitiveKey($match[1])
                ? $match[1] . '=' . self::REDACTED
                : $match[0],
            $text
        );
    }
}
