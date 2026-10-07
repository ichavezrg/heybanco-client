<?php

namespace Ichavezrg\HeyBancoClient\Caas\Misc;

use DateTimeInterface;
use Ichavezrg\HeyBancoClient\Auth;
use Ichavezrg\HeyBancoClient\Client;
use Ichavezrg\HeyBancoClient\Signature;

class Webhook
{
    private const DATE_FORMAT = 'Y-m-d';

    public function __construct(
        private readonly Client $client,
        private readonly Auth $auth,
        private readonly Signature $signature,
        private readonly WebhookApiVersion $version = WebhookApiVersion::V1_0,
    ) {}

    /**
     * Devuelve una copia que consume la versión indicada de la API, sin
     * modificar la instancia original.
     */
    public function withVersion(WebhookApiVersion $version): self
    {
        return new self($this->client, $this->auth, $this->signature, $version);
    }

    public function version(): WebhookApiVersion
    {
        return $this->version;
    }

    /**
     * @param int|null $page
     * @param int|null $size 10, 100 o 1000
     */
    public function findAll(?int $page = null, ?int $size = null): array
    {
        $response = $this->client->http()->get($this->path(), $this->options([
            'query' => $this->query(['page' => $page, 'size' => $size]),
        ]));

        $payload = json_decode($response->getBody()->getContents(), true);

        return $this->signature->decrypt($payload['data']);
    }

    public function find(int $webhookId): array
    {
        $response = $this->client->http()->get($this->path("/{$webhookId}"), $this->options());

        $payload = json_decode($response->getBody()->getContents(), true);

        return $this->signature->decrypt($payload['data']);
    }

    /**
     * @param array<array{id: int, version?: string}> $events
     * @return array<string, mixed> Cuerpo de la respuesta; incluye `webhookId` cuando HeyBanco lo informa en el header Location.
     */
    public function create(string $authenticationUrl, string $notificationUrl, string $authorizationUrl, array $events): array
    {
        $payload = [
            "authenticationType" => "C",
            "clientId" => $this->auth->clientId,
            "clientSecret" => $this->auth->clientSecret,
            "authenticationUrl" => $authenticationUrl,
            "notificationUrl" => $notificationUrl,
            "authorizationUrl" => $authorizationUrl,
            "events" => $events
        ];

        $response = $this->client->http()->post($this->path(), $this->options([
            "json" => [
                "data" => $this->signature->sign($payload)
            ]
        ]));

        $body = json_decode($response->getBody()->getContents(), true) ?? [];
        $webhookId = $this->webhookIdFromLocation($response->getHeaderLine('Location'));

        return $webhookId === null ? $body : $body + ['webhookId' => $webhookId];
    }

    /**
     * Actualiza URLs y credenciales de un webhook. Los eventos suscritos no se
     * pueden modificar: hay que eliminar el webhook y volver a registrarlo.
     */
    public function update(int $webhookId, string $authenticationUrl, string $notificationUrl, string $authorizationUrl): array
    {
        $payload = [
            "authenticationType" => "C",
            "clientId" => $this->auth->clientId,
            "clientSecret" => $this->auth->clientSecret,
            "authenticationUrl" => $authenticationUrl,
            "notificationUrl" => $notificationUrl,
            "authorizationUrl" => $authorizationUrl,
        ];

        $response = $this->client->http()->patch($this->path("/{$webhookId}"), $this->options([
            "json" => [
                "data" => $this->signature->sign($payload)
            ]
        ]));

        return json_decode($response->getBody()->getContents(), true) ?? [];
    }

    public function delete(int $webhookId): array
    {
        $response = $this->client->http()->delete($this->path("/{$webhookId}"), $this->options());

        return json_decode($response->getBody()->getContents(), true);
    }

    public function showEvents(?int $page = null, ?int $size = null): array
    {
        $response = $this->client->http()->get($this->path('/events'), $this->options([
            'query' => $this->query(['page' => $page, 'size' => $size]),
        ]));

        $payload = json_decode($response->getBody()->getContents(), true);

        return $this->signature->decrypt($payload['data']);
    }

    /**
     * Consulta las notificaciones generadas para la suscripción (solo v1.1).
     * Con $eventId se llena metadata.lastSequence. Sin filtro de fechas HeyBanco
     * limita `from` a 90 días de antigüedad.
     *
     * @param int|null $eventId ID del evento del catálogo
     * @param int|null $size 10, 100 o 1000
     * @return array{code?: string, message?: string, data: array, metadata?: array}
     */
    public function notifications(
        ?int $eventId = null,
        ?DateTimeInterface $from = null,
        ?DateTimeInterface $to = null,
        ?int $page = null,
        ?int $size = null,
    ): array {
        $this->requireVersion(WebhookApiVersion::V1_1, 'notifications');

        $response = $this->client->http()->get($this->path('/notifications'), $this->options([
            'query' => $this->query([
                'idEvent' => $eventId,
                'from' => $from?->format(self::DATE_FORMAT),
                'to' => $to?->format(self::DATE_FORMAT),
                'page' => $page,
                'size' => $size,
            ]),
        ]));

        $payload = json_decode($response->getBody()->getContents(), true);

        if (is_string($payload['data'] ?? null)) {
            $payload['data'] = $this->signature->decrypt($payload['data']);
        }

        return $payload;
    }

    /**
     * Pide a HeyBanco reenviar notificaciones (solo v1.1), por rango de
     * secuencia (máximo 100) o por clave de rastreo; los modos son excluyentes.
     * Los reenvíos llegan después a la notificationUrl del webhook.
     *
     * @param string $event eventType del catálogo, p. ej. collection-agreement.processed
     */
    public function resend(
        string $event,
        DateTimeInterface $date,
        ?int $fromSequence = null,
        ?int $toSequence = null,
        ?string $trackingNumber = null,
    ): array {
        $this->requireVersion(WebhookApiVersion::V1_1, 'resend');

        $payload = ['event' => $event, 'date' => $date->format(self::DATE_FORMAT)]
            + $this->query([
                'fromSequence' => $fromSequence,
                'toSequence' => $toSequence,
                'trackingNumber' => $trackingNumber,
            ]);

        $response = $this->client->http()->post($this->path('/resend'), $this->options([
            "json" => [
                "data" => $this->signature->sign($payload)
            ]
        ]));

        return json_decode($response->getBody()->getContents(), true) ?? [];
    }

    private function path(string $suffix = ''): string
    {
        return "/misc/{$this->version->value}/webhooks{$suffix}";
    }

    /**
     * @param array<string, mixed> $options Opciones extra de Guzzle (query, json)
     * @return array<string, mixed>
     */
    private function options(array $options = []): array
    {
        $accessToken = $this->auth->generateToken();

        return $options + [
            "headers" => [
                "Authorization" => "Bearer " . $accessToken['access_token'],
                "B-Option" => 0,
                "B-Transaction" => $this->auth->generateBTransaction(),
                "B-Application" => $this->client->bApplication,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed> Solo los parámetros con valor
     */
    private function query(array $params): array
    {
        return array_filter($params, fn ($value) => $value !== null);
    }

    private function requireVersion(WebhookApiVersion $required, string $operation): void
    {
        if ($this->version !== $required) {
            throw new \LogicException(
                "La operación {$operation} solo existe en la versión {$required->value} de la API de webhooks."
            );
        }
    }

    private function webhookIdFromLocation(string $location): ?int
    {
        return preg_match('#/(\d+)/?$#', $location, $matches) === 1 ? (int) $matches[1] : null;
    }
}
