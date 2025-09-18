<?php

namespace Ichavezrg\HeyBancoClient\Caas;

use DateTimeImmutable;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Ichavezrg\HeyBancoClient\Auth;
use Ichavezrg\HeyBancoClient\Client;
use Ichavezrg\HeyBancoClient\Signature;

class Collection
{
    public function __construct(
        private readonly Client $client,
        private readonly Auth $auth,
        private readonly Signature $signature
    ) {}

    /**
     * Consulta domiciliación de cargos previamente programadas.
     *
     * @param int $agreementId
     * @return array
     * @throws \Exception
     */
    public function find(
        int $agreementId,
        int $page,
        int $size,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
        ?string $reference = null,
        ?string $accountNumber = null
    ): array {
        $accessToken = $this->auth->generateToken(
            $this->auth->clientId,
            $this->auth->clientSecret
        );

        $bTransaction = $this->auth->generateBTransaction();
        try {
            $response = $this->client->http()->get("/caas/v1.0/agreements/{$agreementId}/collections", [
                "headers" => [
                    "Authorization" => "Bearer " . $accessToken['access_token'],
                    "B-Option" => 0,
                    "B-Transaction" => $bTransaction,
                    "B-Application" => $this->client->bApplication,
                ],
                "query" => [
                    "reference" => $reference,
                    "accountNumber" => $accountNumber,
                    "from" => $from?->format('Y-m-d'),
                    "to" => $to?->format('Y-m-d'),
                    "page" => $page,
                    "size" => $size,
                ],
            ]);

            $payload = json_decode($response->getBody()->getContents(), true);

            $uncryptedPayload = $this->signature->decrypt($payload["data"]);
            $payload["data"] = $uncryptedPayload;

            return $payload;
        } catch (ClientException | ServerException $e) {
            if ($e->getCode() === 404) {
                $responseArray = json_decode($e->getResponse()->getBody()->getContents(), true);
                if ($responseArray["code"] === "NF-65") {
                    // collections not found
                    return [];
                }
            }
            print_r($e->getResponse()->getBody()->getContents());
            throw $e;
        }
    }

    /**
     * Programa cargos de domiciliación.
     *
     * @param int $agreementIds
     * @param int $userId
     * @param string $reference
     * @param float $amount
     * @param Periodicity $periodicity
     * @param DateTimeImmutable $firstCollectionDate
     * @param CollectionValidity $collectionValidity
     * @return array
     * @throws \Exception
     */
    public function create(
        int $agreementId,
        string $verificationCode,
        int $userId,
        string $reference,
        float $amount,
        Periodicity $periodicity,
        DateTimeImmutable $firstCollectionDate,
        CollectionValidity $collectionValidity
    ): array {
        $accessToken = $this->auth->generateToken(
            $this->auth->clientId,
            $this->auth->clientSecret
        );

        $bTransaction = $this->auth->generateBTransaction();

        $payload = [
            "userId" => $userId,
            "reference" => $reference,
            "amount" => $amount,
            "periodicityId" => $periodicity->value,
            "firstCollectionDate" => $firstCollectionDate->format('Y-m-d'),
            "collectionValidity" => $collectionValidity->toArray()
        ];

        $response = $this->client->http()->post("/caas/v1.0/agreements/{$agreementId}/collections", [
            "headers" => [
                "Authorization" => "Bearer " . $accessToken['access_token'],
                "B-Option" => 0,
                "B-Transaction" => $bTransaction,
                "B-Application" => $this->client->bApplication,
                "B-authentication-code" => $verificationCode,
            ],
            "json" => [
                "data" => $this->signature->sign($payload)
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Cancelación de la cobranza domiciliada.
     *
     * Se aceptan de 1 a 100 cobranzas domiciliadas por solicitud.
     *
     * @param string $agreementId
     * @param array $collectionIds
     * @return array
     * @throws \Exception
     */
    public function cancel(string $agreementId, array $collectionIds): array
    {
        $accessToken = $this->auth->generateToken(
            $this->auth->clientId,
            $this->auth->clientSecret
        );

        $bTransaction = $this->auth->generateBTransaction();

        $response = $this->client->http()->delete("/caas/v1.0/agreements/{$agreementId}/collections", [
            "headers" => [
                "Authorization" => "Bearer " . $accessToken['access_token'],
                "B-Option" => 0,
                "B-Transaction" => $bTransaction,
                "B-Application" => $this->client->bApplication,
            ],
            "json" => [
                "data" => $this->signature->sign($collectionIds)
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}
