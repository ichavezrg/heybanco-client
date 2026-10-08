<?php

namespace Ichavezrg\HeyBancoClient\Tests;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Ichavezrg\HeyBancoClient\Auth;
use Ichavezrg\HeyBancoClient\Client;
use Ichavezrg\HeyBancoClient\LogSanitizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Lo que el cliente escribe en el log al hablar con Hey: sin credenciales ni
 * tokens, pero con lo necesario para investigar una llamada.
 */
class ClientLoggingTest extends TestCase
{
    private const CLIENT_SECRET = 'S3cr3t-client-secret-9f8e7d';
    private const ACCESS_TOKEN = 'eyJhbGciOiJSUzI1NiJ9.ACCESS-TOKEN-PAYLOAD.signature-a';
    private const ID_TOKEN = 'eyJhbGciOiJSUzI1NiJ9.ID-TOKEN-PAYLOAD.signature-b';
    private const REFRESH_TOKEN = 'eyJhbGciOiJIUzI1NiJ9.REFRESH-TOKEN-PAYLOAD.signature-c';
    private const KEYSTORE_PASSWORD = 'keystore-password-4d3c2b';
    private const SECRETS = [
        self::CLIENT_SECRET,
        self::ACCESS_TOKEN,
        self::ID_TOKEN,
        self::REFRESH_TOKEN,
        self::KEYSTORE_PASSWORD,
    ];

    private MockHandler $mock;

    /** @var array<int, array{level: string, message: string, context: array<mixed>}> */
    private array $records = [];

    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        $this->mock = new MockHandler();
        $this->records = [];

        $records = &$this->records;
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->logger->method('info')->willReturnCallback(
            function (string|\Stringable $message, array $context = []) use (&$records): void {
                $records[] = ['level' => 'info', 'message' => (string) $message, 'context' => $context];
            }
        );
    }

    private function client(?LoggerInterface $logger): Client
    {
        return new Client(
            host: 'https://api-tech.hey.inc',
            bApplication: 'b-application-1',
            certPath: 'tests/certs/client.crt',
            keyPath: 'tests/certs/client.key',
            mtlsKeystorePassword: self::KEYSTORE_PASSWORD,
            handlerStack: HandlerStack::create($this->mock),
            logger: $logger,
        );
    }

    private function queueTokenResponse(): void
    {
        $headers = ['Content-Type' => 'application/json', 'Set-Cookie' => 'KC_SESSION=abc123; HttpOnly'];

        $this->mock->append(new Response(200, $headers, (string) json_encode([
            'access_token' => self::ACCESS_TOKEN,
            'id_token' => self::ID_TOKEN,
            'refresh_token' => self::REFRESH_TOKEN,
            'expires_in' => 300,
            'refresh_expires_in' => 1800,
            'token_type' => 'Bearer',
            'scope' => 'openid',
        ])));
    }

    private function logged(): string
    {
        return (string) json_encode($this->records, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function testRequestingATokenNeverLogsTheCredentialsNorTheTokens(): void
    {
        $this->queueTokenResponse();
        $auth = new Auth($this->client($this->logger), 'client-id-1', self::CLIENT_SECRET);

        $auth->generateToken();

        $this->assertCount(1, $this->records);
        $log = $this->logged();

        foreach (self::SECRETS as $secret) {
            $this->assertStringNotContainsString($secret, $log);
        }

        $this->assertStringContainsString(LogSanitizer::REDACTED, $log);
    }

    public function testTheTokenCallStillLogsWhatIsNeededToInvestigateIt(): void
    {
        $this->queueTokenResponse();
        $auth = new Auth($this->client($this->logger), 'client-id-1', self::CLIENT_SECRET);

        $auth->generateToken();

        $record = $this->records[0];
        $this->assertSame('info', $record['level']);
        $this->assertSame('POST : /auth/v1/oidc/token', $record['message']);
        $this->assertSame([
            'client_id' => 'client-id-1',
            'client_secret' => LogSanitizer::REDACTED,
            'grant_type' => 'client_credentials',
            'scope' => 'openid',
        ], $record['context']['request']['form_params']);
        $response = json_decode($record['context']['response'], true);
        $this->assertSame(300, $response['expires_in']);
        $this->assertSame('Bearer', $response['token_type']);
        $this->assertSame(LogSanitizer::REDACTED, $response['access_token']);
        $this->assertSame(LogSanitizer::REDACTED, $record['context']['headers']['Set-Cookie']);
        $this->assertSame(['application/json'], $record['context']['headers']['Content-Type']);
    }

    public function testTheBearerOfAnAuthenticatedCallIsNotLogged(): void
    {
        $this->mock->append(new Response(200, [], 'OK-22'));
        $client = $this->client($this->logger);

        $client->http()->request('OPTIONS', '/caas/v1.0/agreements', [
            'headers' => ['Authorization' => 'Bearer ' . self::ACCESS_TOKEN, 'B-Transaction' => '654321'],
        ]);

        $log = $this->logged();
        $this->assertStringNotContainsString(self::ACCESS_TOKEN, $log);
        $this->assertSame(LogSanitizer::REDACTED, $this->records[0]['context']['request']['headers']['Authorization']);
        $this->assertSame('654321', $this->records[0]['context']['request']['headers']['B-Transaction']);
        $this->assertSame('OK-22', $this->records[0]['context']['response']);
    }

    public function testConnectionOptionsWithPasswordsAreNeverLogged(): void
    {
        $this->mock->append(new Response(200, [], 'OK-22'));

        $this->client($this->logger)->http()->request('GET', '/x', [
            'cert' => ['/ruta/cert.pem', self::KEYSTORE_PASSWORD],
            'ssl_key' => ['/ruta/key.pem', self::KEYSTORE_PASSWORD],
            'curl' => [CURLOPT_SSLCERTPASSWD => self::KEYSTORE_PASSWORD],
        ]);

        $this->assertStringNotContainsString(self::KEYSTORE_PASSWORD, $this->logged());
        $this->assertSame([], $this->records[0]['context']['request']);
    }

    public function testACredentialInTheUriQueryIsNotLogged(): void
    {
        $this->mock->append(new Response(200, [], 'OK'));

        $uri = '/x?client_secret=' . self::CLIENT_SECRET . '&accountNumber=0123';

        $this->client($this->logger)->http()->request('GET', $uri);

        $this->assertStringNotContainsString(self::CLIENT_SECRET, $this->logged());
        $this->assertSame(
            'GET : /x?client_secret=' . LogSanitizer::REDACTED . '&accountNumber=0123',
            $this->records[0]['message']
        );
    }

    public function testTheCallerStillGetsTheCompleteResponseAfterItWasLogged(): void
    {
        $this->queueTokenResponse();
        $auth = new Auth($this->client($this->logger), 'client-id-1', self::CLIENT_SECRET);

        $token = $auth->generateToken();

        $this->assertSame(self::ACCESS_TOKEN, $token['access_token']);
        $this->assertSame(self::ID_TOKEN, $token['id_token']);
        $this->assertSame(self::REFRESH_TOKEN, $token['refresh_token']);
    }

    public function testWithoutALoggerNothingIsLoggedAndTheCallWorks(): void
    {
        $this->queueTokenResponse();
        $auth = new Auth($this->client(null), 'client-id-1', self::CLIENT_SECRET);

        $token = $auth->generateToken();

        $this->assertSame(self::ACCESS_TOKEN, $token['access_token']);
        $this->assertSame([], $this->records);
    }

    public function testAnOpaqueEncryptedResponseIsLoggedAsIs(): void
    {
        $jwe = 'eyJhbGciOiJSU0EtT0FFUC0yNTYiLCJlbmMiOiJBMjU2R0NNIn0.AbCd.EfGh.IjKl.MnOp';
        $this->mock->append(new Response(200, [], $jwe));

        $this->client($this->logger)->http()->request('GET', '/caas/v1.0/agreements/6');

        $this->assertSame($jwe, $this->records[0]['context']['response']);
    }
}
