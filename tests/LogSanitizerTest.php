<?php

namespace Ichavezrg\HeyBancoClient\Tests;

use Ichavezrg\HeyBancoClient\LogSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LogSanitizerTest extends TestCase
{
    private const R = LogSanitizer::REDACTED;

    /**
     * @return array<string, array{string}>
     */
    public static function sensitiveKeys(): array
    {
        return [
            'client_secret' => ['client_secret'],
            'camelCase' => ['clientSecret'],
            'mayúsculas' => ['CLIENT_SECRET'],
            'access_token' => ['access_token'],
            'id_token' => ['id_token'],
            'refresh_token' => ['refresh_token'],
            'token a secas' => ['token'],
            'Authorization' => ['Authorization'],
            'Proxy-Authorization' => ['Proxy-Authorization'],
            'Cookie' => ['Cookie'],
            'Set-Cookie' => ['Set-Cookie'],
            'password' => ['password'],
            'mtlsKeystorePassword' => ['mtlsKeystorePassword'],
            'passphrase' => ['privateKeyPhrase_passphrase'],
            'private key' => ['private_key'],
            'X-Api-Key' => ['X-Api-Key'],
        ];
    }

    #[DataProvider('sensitiveKeys')]
    public function testSensitiveKeysAreRedacted(string $key): void
    {
        $this->assertTrue(LogSanitizer::isSensitiveKey($key));
        $this->assertSame([$key => self::R], LogSanitizer::context([$key => 'valor-secreto']));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function harmlessKeys(): array
    {
        return [
            'token_type' => ['token_type'],
            'expires_in' => ['expires_in'],
            'refresh_expires_in' => ['refresh_expires_in'],
            'scope' => ['scope'],
            'client_id' => ['client_id'],
            'B-Application' => ['B-Application'],
            'B-Transaction' => ['B-Transaction'],
            'Content-Type' => ['Content-Type'],
            'grant_type' => ['grant_type'],
            'vacía' => [''],
        ];
    }

    #[DataProvider('harmlessKeys')]
    public function testHarmlessKeysAreKept(string $key): void
    {
        $this->assertFalse(LogSanitizer::isSensitiveKey($key));
        $this->assertSame([$key => 'visible'], LogSanitizer::context([$key => 'visible']));
    }

    public function testNestedArraysAreSanitizedAtEveryLevel(): void
    {
        $clean = LogSanitizer::context([
            'a' => ['b' => ['client_secret' => 'x', 'ok' => 'y'], 'lista' => [['access_token' => 'z', 'n' => 1]]],
        ]);

        $this->assertSame([
            'a' => [
                'b' => ['client_secret' => self::R, 'ok' => 'y'],
                'lista' => [['access_token' => self::R, 'n' => 1]],
            ],
        ], $clean);
    }

    public function testNonStringValuesOfSensitiveKeysAreRedactedToo(): void
    {
        $this->assertSame(
            ['access_token' => self::R, 'password' => self::R],
            LogSanitizer::context(['access_token' => ['a', 'b'], 'password' => 12345])
        );
    }

    public function testNumericKeysAndScalarsAreLeftAlone(): void
    {
        $values = ['a', 2, true, null, 1.5];

        $this->assertSame($values, LogSanitizer::context($values));
    }

    public function testAJsonBodyIsSanitizedByFieldAndStaysJson(): void
    {
        $body = (string) json_encode([
            'access_token' => 'eyJ.access.sig',
            'id_token' => 'eyJ.id.sig',
            'refresh_token' => 'eyJ.refresh.sig',
            'expires_in' => 300,
            'token_type' => 'Bearer',
            'url' => 'https://api-tech.hey.inc/x',
        ], JSON_UNESCAPED_SLASHES);

        $clean = LogSanitizer::text($body);

        $this->assertSame([
            'access_token' => self::R,
            'id_token' => self::R,
            'refresh_token' => self::R,
            'expires_in' => 300,
            'token_type' => 'Bearer',
            'url' => 'https://api-tech.hey.inc/x',
        ], json_decode($clean, true));
        $this->assertStringNotContainsString('eyJ.', $clean);
    }

    public function testAJsonListBodyIsSanitized(): void
    {
        $clean = LogSanitizer::text('[{"client_secret":"s","id":1}]');

        $this->assertSame([['client_secret' => self::R, 'id' => 1]], json_decode($clean, true));
    }

    public function testAFormEncodedBodyIsSanitizedKeepingTheRest(): void
    {
        $clean = LogSanitizer::text('client_id=abc&client_secret=s3cr3t&grant_type=client_credentials&scope=openid');

        $this->assertSame(
            'client_id=abc&client_secret=' . self::R . '&grant_type=client_credentials&scope=openid',
            $clean
        );
    }

    public function testBearerTokensInFreeTextAreRedacted(): void
    {
        $this->assertSame(
            'Falló con Authorization: Bearer ' . self::R . ' en la llamada',
            LogSanitizer::text('Falló con Authorization: Bearer eyJhbGciOi.J9.abc-DEF_123= en la llamada')
        );
    }

    public function testPlainTextWithoutSecretsIsUntouched(): void
    {
        foreach (['OK-22', 'GET : /caas/v1.0/agreements/6?accountNumber=012345', '', '{ no es json', 'a=b'] as $text) {
            $this->assertSame($text, LogSanitizer::text($text));
        }
    }

    public function testAnOpaqueJweBodyIsNotAltered(): void
    {
        $jwe = 'eyJhbGciOiJSU0EtT0FFUC0yNTYiLCJlbmMiOiJBMjU2R0NNIn0.AbCd.EfGh.IjKl.MnOp';

        $this->assertSame($jwe, LogSanitizer::text($jwe));
    }

    public function testRequestKeepsOnlyTheLoggedOptionsAndSanitizesThem(): void
    {
        $clean = LogSanitizer::request([
            'headers' => [
                'Authorization' => 'Bearer abc.def',
                'B-Application' => 'app-1',
                'Accept' => 'application/json',
            ],
            'form_params' => ['client_id' => 'id', 'client_secret' => 'shh', 'grant_type' => 'client_credentials'],
            'query' => ['accountNumber' => '0123'],
            'json' => ['password' => 'p', 'monto' => 10],
            'body' => 'client_secret=shh&scope=openid',
            'cert' => ['/ruta/cert.pem', 'contraseña-del-keystore'],
            'ssl_key' => ['/ruta/key.pem', 'otra-contraseña'],
            'auth' => ['usuario', 'clave'],
            'curl' => [CURLOPT_SSLCERTPASSWD => 'contraseña-del-keystore'],
            'verify' => false,
        ]);

        $this->assertSame(['headers', 'query', 'json', 'form_params', 'body'], array_keys($clean));
        $this->assertSame(
            ['Authorization' => self::R, 'B-Application' => 'app-1', 'Accept' => 'application/json'],
            $clean['headers']
        );
        $this->assertSame(
            ['client_id' => 'id', 'client_secret' => self::R, 'grant_type' => 'client_credentials'],
            $clean['form_params']
        );
        $this->assertSame(['accountNumber' => '0123'], $clean['query']);
        $this->assertSame(['password' => self::R, 'monto' => 10], $clean['json']);
        $this->assertSame('client_secret=' . self::R . '&scope=openid', $clean['body']);
        $this->assertStringNotContainsString('contraseña', (string) json_encode($clean));
    }

    public function testAStreamBodyIsNotReadAndGetsAMarker(): void
    {
        $stream = fopen('php://memory', 'r+');
        $this->assertNotFalse($stream);
        fwrite($stream, 'client_secret=shh');

        $clean = LogSanitizer::request(['body' => $stream]);

        $this->assertSame('[resource]', $clean['body']);
        rewind($stream);
        $this->assertSame('client_secret=shh', stream_get_contents($stream), 'No se consumió el stream.');
    }

    public function testResponseHeadersHideCookiesButKeepTheRest(): void
    {
        $clean = LogSanitizer::headers([
            'Set-Cookie' => ['session=abc; HttpOnly'],
            'Content-Type' => ['application/json'],
            'B-Transaction' => ['123456'],
        ]);

        $this->assertSame(
            ['Set-Cookie' => self::R, 'Content-Type' => ['application/json'], 'B-Transaction' => ['123456']],
            $clean
        );
    }
}
