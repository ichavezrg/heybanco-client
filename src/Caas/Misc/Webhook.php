<?php

namespace Ichavezrg\HeyBancoClient\Caas\Misc;

use Ichavezrg\HeyBancoClient\Auth;
use Ichavezrg\HeyBancoClient\Client;
use Ichavezrg\HeyBancoClient\Signature;

class Webhook
{
    public function __construct(
        private readonly Client $client,
        private readonly Auth $auth,
        private readonly Signature $signature
    ) {}

    public function findAll(): array
    {
        $accessToken = $this->auth->generateToken(
            $this->auth->clientId,
            $this->auth->clientSecret
        );

        $bTransaction = $this->auth->generateBTransaction();

        $response = $this->client->http()->get("/misc/v1.0/webhooks", [
            "headers" => [
                "Authorization" => "Bearer " . $accessToken['access_token'],
                "B-Option" => 0,
                "B-Transaction" => $bTransaction,
                "B-Application" => $this->client->bApplication,
            ],
        ]);

        $payload = json_decode($response->getBody()->getContents(), true);

        return $this->signature->decrypt($payload['data']);
    }

    public function create(string $authenticationUrl, string $notificationUrl, string $authorizationUrl, array $events): array
    {
        $accessToken = $this->auth->generateToken(
            $this->auth->clientId,
            $this->auth->clientSecret
        );

        $bTransaction = $this->auth->generateBTransaction();

        $payload = [
            "authenticationType" => "C",
            "clientId" => $this->auth->clientId,
            "clientSecret" => $this->auth->clientSecret,
            "authenticationUrl" => $authenticationUrl,
            "notificationUrl" => $notificationUrl,
            "authorizationUrl" => $authorizationUrl,
            "events" => $events
        ];

        $response = $this->client->http()->post("/misc/v1.0/webhooks", [
            "headers" => [
                "Authorization" => "Bearer " . $accessToken['access_token'],
                "B-Option" => 0,
                "B-Transaction" => $bTransaction,
                "B-Application" => $this->client->bApplication,
            ],
            "json" => [
                "data" => $this->signature->sign($payload)
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function delete(int $webhookId): array
    {
        $accessToken = $this->auth->generateToken(
            $this->auth->clientId,
            $this->auth->clientSecret
        );

        $bTransaction = $this->auth->generateBTransaction();

        $response = $this->client->http()->delete("/misc/v1.0/webhooks/{$webhookId}", [
            "headers" => [
                "Authorization" => "Bearer " . $accessToken['access_token'],
                "B-Option" => 0,
                "B-Transaction" => $bTransaction,
                "B-Application" => $this->client->bApplication,
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function showEvents(): array
    {
        $accessToken = $this->auth->generateToken(
            $this->auth->clientId,
            $this->auth->clientSecret
        );

        $bTransaction = $this->auth->generateBTransaction();

        $response = $this->client->http()->get("/misc/v1.0/webhooks/events", [
            "headers" => [
                "B-Option" => 0,
                "B-Transaction" => $bTransaction,
                "B-Application" => $this->client->bApplication,
                "Authorization" => "Bearer " . $accessToken['access_token'],
            ],
        ]);

        $payload = json_decode($response->getBody()->getContents(), true);

        return $this->signature->decrypt($payload['data']);
    }
}
