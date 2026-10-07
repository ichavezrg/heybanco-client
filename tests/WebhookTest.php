<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Ichavezrg\HeyBancoClient\Auth;
use Ichavezrg\HeyBancoClient\Caas\Misc\Webhook;
use Ichavezrg\HeyBancoClient\Caas\Misc\WebhookApiVersion;
use Ichavezrg\HeyBancoClient\Client;
use Ichavezrg\HeyBancoClient\Signature;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class WebhookTest extends TestCase
{
    private const CLIENT_ID = 'c78ee0f5-c521-4896-84a0-4ba13ecce4dd';
    private const CLIENT_SECRET = 'secret';
    private const B_APPLICATION = '845b7687-3886-4bb4-be1c-33e45a6c3d34';

    private MockHandler $mock;

    /** @var array<int, array{request: RequestInterface}> */
    private array $history = [];

    private Signature&MockObject $signature;

    private Webhook $webhook;

    public function setUp(): void
    {
        parent::setUp();

        $this->mock = new MockHandler();
        $stack = HandlerStack::create($this->mock);
        $stack->push(Middleware::history($this->history));

        $client = new Client(
            host: 'https://sbox-api-tech.hey.inc',
            bApplication: self::B_APPLICATION,
            certPath: 'tests/certs/client.crt',
            keyPath: 'tests/certs/client.key',
            mtlsKeystorePassword: 'password',
            handlerStack: $stack,
        );

        $this->signature = $this->createMock(Signature::class);

        $this->webhook = new Webhook(
            $client,
            new Auth($client, self::CLIENT_ID, self::CLIENT_SECRET),
            $this->signature,
        );
    }

    public function testDefaultsToVersion10(): void
    {
        $this->queueApiResponse(['data' => 'encrypted']);
        $this->signature->method('decrypt')->willReturn([]);

        $this->webhook->findAll();

        $this->assertSame(WebhookApiVersion::V1_0, $this->webhook->version());
        $this->assertSame('/misc/v1.0/webhooks', $this->apiRequest()->getUri()->getPath());
    }

    public function testWithVersionReturnsACopyAndKeepsTheOriginal(): void
    {
        $v11 = $this->webhook->withVersion(WebhookApiVersion::V1_1);

        $this->assertNotSame($this->webhook, $v11);
        $this->assertSame(WebhookApiVersion::V1_1, $v11->version());
        $this->assertSame(WebhookApiVersion::V1_0, $this->webhook->version());
    }

    public function testFindAllSendsAuthHeadersAndPaginationAndDecrypts(): void
    {
        $this->queueApiResponse(['data' => 'encrypted']);
        $this->signature->expects($this->once())->method('decrypt')->with('encrypted')->willReturn([['id' => 7]]);

        $result = $this->webhook->withVersion(WebhookApiVersion::V1_1)->findAll(page: 2, size: 100);

        $this->assertSame([['id' => 7]], $result);
        $request = $this->apiRequest();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/misc/v1.1/webhooks', $request->getUri()->getPath());
        $this->assertSame('page=2&size=100', $request->getUri()->getQuery());
        $this->assertSame('Bearer access-token', $request->getHeaderLine('Authorization'));
        $this->assertSame(self::B_APPLICATION, $request->getHeaderLine('B-Application'));
        $this->assertSame('0', $request->getHeaderLine('B-Option'));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $request->getHeaderLine('B-Transaction'));
    }

    public function testFindAllOmitsPaginationWhenNotGiven(): void
    {
        $this->queueApiResponse(['data' => 'encrypted']);
        $this->signature->method('decrypt')->willReturn([]);

        $this->webhook->findAll();

        $this->assertSame('', $this->apiRequest()->getUri()->getQuery());
    }

    public function testFindFetchesASingleWebhook(): void
    {
        $this->queueApiResponse(['data' => 'encrypted']);
        $this->signature->method('decrypt')->willReturn(['id' => 12]);

        $result = $this->webhook->withVersion(WebhookApiVersion::V1_1)->find(12);

        $this->assertSame(['id' => 12], $result);
        $this->assertSame('/misc/v1.1/webhooks/12', $this->apiRequest()->getUri()->getPath());
    }

    public function testCreateSignsThePayloadAndReturnsTheWebhookIdFromLocation(): void
    {
        $events = [['id' => 4, 'version' => '1.1.0']];
        $this->signature->expects($this->once())->method('sign')
            ->with([
                'authenticationType' => 'C',
                'clientId' => self::CLIENT_ID,
                'clientSecret' => self::CLIENT_SECRET,
                'authenticationUrl' => 'https://rg.test/auth',
                'notificationUrl' => 'https://rg.test/notif',
                'authorizationUrl' => 'https://rg.test/authz',
                'events' => $events,
            ])
            ->willReturn('signed-payload');
        $this->queueApiResponse(
            ['code' => 'CR-01', 'message' => 'Webhook registered successfully.'],
            201,
            ['Location' => 'webhooks/15']
        );

        $result = $this->webhook->withVersion(WebhookApiVersion::V1_1)
            ->create('https://rg.test/auth', 'https://rg.test/notif', 'https://rg.test/authz', $events);

        $this->assertSame(
            ['code' => 'CR-01', 'message' => 'Webhook registered successfully.', 'webhookId' => 15],
            $result
        );
        $request = $this->apiRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/misc/v1.1/webhooks', $request->getUri()->getPath());
        $this->assertSame(['data' => 'signed-payload'], json_decode((string) $request->getBody(), true));
    }

    public function testCreateKeepsTheBodyWhenThereIsNoLocationHeader(): void
    {
        $this->signature->method('sign')->willReturn('signed-payload');
        $this->queueApiResponse(['id' => 10, 'status' => 'CREATED'], 201);

        $result = $this->webhook->create('https://rg.test/auth', 'https://rg.test/notif', 'https://rg.test/authz', [['id' => 4]]);

        $this->assertSame(['id' => 10, 'status' => 'CREATED'], $result);
    }

    public function testUpdateSendsASignedPatchWithoutEvents(): void
    {
        $this->signature->expects($this->once())->method('sign')
            ->with([
                'authenticationType' => 'C',
                'clientId' => self::CLIENT_ID,
                'clientSecret' => self::CLIENT_SECRET,
                'authenticationUrl' => 'https://rg.test/auth',
                'notificationUrl' => 'https://rg.test/notif',
                'authorizationUrl' => 'https://rg.test/authz',
            ])
            ->willReturn('signed-payload');
        $this->queueApiResponse(['code' => 'OK-01']);

        $result = $this->webhook->withVersion(WebhookApiVersion::V1_1)
            ->update(15, 'https://rg.test/auth', 'https://rg.test/notif', 'https://rg.test/authz');

        $this->assertSame(['code' => 'OK-01'], $result);
        $request = $this->apiRequest();
        $this->assertSame('PATCH', $request->getMethod());
        $this->assertSame('/misc/v1.1/webhooks/15', $request->getUri()->getPath());
        $this->assertSame(['data' => 'signed-payload'], json_decode((string) $request->getBody(), true));
    }

    public function testDeleteUsesTheVersionPath(): void
    {
        $this->queueApiResponse(['code' => 'OK-01']);

        $result = $this->webhook->withVersion(WebhookApiVersion::V1_1)->delete(15);

        $this->assertSame(['code' => 'OK-01'], $result);
        $request = $this->apiRequest();
        $this->assertSame('DELETE', $request->getMethod());
        $this->assertSame('/misc/v1.1/webhooks/15', $request->getUri()->getPath());
    }

    public function testShowEventsUsesTheVersionPathAndDecrypts(): void
    {
        $this->queueApiResponse(['data' => 'encrypted']);
        $this->signature->method('decrypt')->willReturn([['id' => 9]]);

        $result = $this->webhook->withVersion(WebhookApiVersion::V1_1)->showEvents();

        $this->assertSame([['id' => 9]], $result);
        $this->assertSame('/misc/v1.1/webhooks/events', $this->apiRequest()->getUri()->getPath());
    }

    public function testNotificationsSendsFiltersAndDecryptsTheData(): void
    {
        $this->queueApiResponse([
            'code' => 'OK-01',
            'data' => 'encrypted',
            'metadata' => ['lastSequence' => 2, 'pagination' => ['page' => 1, 'size' => 100, 'total' => 1, 'items' => 2]],
        ]);
        $this->signature->expects($this->once())->method('decrypt')->with('encrypted')
            ->willReturn([['id' => 'n-1', 'sequence' => 1, 'httpStatusCode' => '404']]);

        $result = $this->webhook->withVersion(WebhookApiVersion::V1_1)->notifications(
            eventId: 9,
            from: new DateTimeImmutable('2026-10-01'),
            to: new DateTimeImmutable('2026-10-06'),
            page: 1,
            size: 100,
        );

        $this->assertSame([['id' => 'n-1', 'sequence' => 1, 'httpStatusCode' => '404']], $result['data']);
        $this->assertSame(2, $result['metadata']['lastSequence']);
        $request = $this->apiRequest();
        $this->assertSame('/misc/v1.1/webhooks/notifications', $request->getUri()->getPath());
        $this->assertSame('idEvent=9&from=2026-10-01&to=2026-10-06&page=1&size=100', $request->getUri()->getQuery());
    }

    public function testNotificationsKeepsDataThatArrivesInClear(): void
    {
        $this->queueApiResponse(['code' => 'OK-01', 'data' => [['id' => 'n-1']]]);
        $this->signature->expects($this->never())->method('decrypt');

        $result = $this->webhook->withVersion(WebhookApiVersion::V1_1)->notifications();

        $this->assertSame([['id' => 'n-1']], $result['data']);
        $this->assertSame('', $this->apiRequest()->getUri()->getQuery());
    }

    public function testNotificationsAreNotAvailableInVersion10(): void
    {
        $this->expectException(LogicException::class);

        $this->webhook->notifications();
    }

    public function testResendByRangeSignsTheSequenceRange(): void
    {
        $this->signature->expects($this->once())->method('sign')
            ->with([
                'event' => 'collection-agreement.processed',
                'date' => '2026-10-05',
                'fromSequence' => 15,
                'toSequence' => 22,
            ])
            ->willReturn('signed-payload');
        $this->queueApiResponse(['code' => 'OK-26']);

        $result = $this->webhook->withVersion(WebhookApiVersion::V1_1)->resend(
            'collection-agreement.processed',
            new DateTimeImmutable('2026-10-05'),
            fromSequence: 15,
            toSequence: 22,
        );

        $this->assertSame(['code' => 'OK-26'], $result);
        $request = $this->apiRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/misc/v1.1/webhooks/resend', $request->getUri()->getPath());
        $this->assertSame(['data' => 'signed-payload'], json_decode((string) $request->getBody(), true));
    }

    public function testResendByTrackingNumberOmitsTheRange(): void
    {
        $this->signature->expects($this->once())->method('sign')
            ->with([
                'event' => 'spei.received',
                'date' => '2025-11-28',
                'trackingNumber' => '058-15/12/2025/15-001HZJ4159',
            ])
            ->willReturn('signed-payload');
        $this->queueApiResponse(['code' => 'OK-26']);

        $this->webhook->withVersion(WebhookApiVersion::V1_1)->resend(
            'spei.received',
            new DateTimeImmutable('2025-11-28'),
            trackingNumber: '058-15/12/2025/15-001HZJ4159',
        );
    }

    public function testResendIsNotAvailableInVersion10(): void
    {
        $this->signature->expects($this->never())->method('sign');
        $this->expectException(LogicException::class);

        $this->webhook->resend('collection-agreement.processed', new DateTimeImmutable('2026-10-05'), 1, 2);
    }

    /**
     * Encola la respuesta del token (que consume cada llamada) y la respuesta de la API.
     *
     * @param array<string, mixed> $body
     * @param array<string, string> $headers
     */
    private function queueApiResponse(array $body, int $status = 200, array $headers = []): void
    {
        $this->mock->append(
            new Response(200, [], json_encode(['access_token' => 'access-token'])),
            new Response($status, $headers, json_encode($body)),
        );
    }

    /** Petición a la API (la primera del historial es la del token). */
    private function apiRequest(): RequestInterface
    {
        $this->assertCount(2, $this->history);
        $this->assertSame('/auth/v1/oidc/token', $this->history[0]['request']->getUri()->getPath());

        return $this->history[1]['request'];
    }
}
