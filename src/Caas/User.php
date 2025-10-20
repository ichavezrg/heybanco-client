<?php

namespace Ichavezrg\HeyBancoClient\Caas;

use GuzzleHttp\Exception\ClientException;
use Ichavezrg\HeyBancoClient\Auth;
use Ichavezrg\HeyBancoClient\Client;
use Ichavezrg\HeyBancoClient\Signature;

class User
{
    public function __construct(
        private readonly Client $client,
        private readonly Auth $auth,
        private readonly Signature $signature
    ) {}

    public function create(int $agreementId, string $verificationCode, array $userRequests): array
    {
        $accessToken = $this->auth->generateToken(
            $this->auth->clientId,
            $this->auth->clientSecret
        );

        $bTransaction = $this->auth->generateBTransaction();

        $response = $this->client->http()->post("/caas/v1.0/agreements/{$agreementId}/users", [
            'headers' => [
                'B-Option' => 0,
                'B-Transaction' => $bTransaction,
                'B-Application' => $this->client->bApplication,
                'Authorization' => 'Bearer ' . $accessToken['access_token'],
                'B-authentication-code' => $verificationCode,
            ],
            'json' => ['data' => $this->signature->sign($userRequests)]
        ]);

        // Al crear un solo usuario en el contrato, la cabecera "Location" contendrá el identificador único del usuario
        // /agreements/62/users/139
        $headers = $response->getHeaders();
        $location = $headers['location'][0];
        $userId = explode('/', $location)[4];

        return ["id" => $userId];
    }

    /**
     * Consulta a un usuario de un contrato de cobranza domiciliada.
     *
     * @param int $agreementId
     * @param int $userId
     * @return array
     * @throws \Exception
     */
    public function show(int $agreementId, int $userId): array
    {
        $accessToken = $this->auth->generateToken(
            $this->auth->clientId,
            $this->auth->clientSecret
        );

        $bTransaction = $this->auth->generateBTransaction();

        $response = $this->client->http()->get("/caas/v1.0/agreements/{$agreementId}/users/{$userId}", [
            'headers' => [
                'B-Option' => 0,
                'B-Transaction' => $bTransaction,
                'B-Application' => $this->client->bApplication,
                'Authorization' => 'Bearer ' . $accessToken['access_token'],
            ],
        ]);

        $payload = json_decode($response->getBody()->getContents(), true);

        return $this->signature->decrypt($payload['data']);
    }

    /**
     * Lista los usuarios registrados en un contrato de cobranza domiciliada.
     *
     * @param string $agreementId
     * @return array
     * @throws \Exception
     */
    public function find(int $agreementId): array
    {
        $accessToken = $this->auth->generateToken(
            $this->auth->clientId,
            $this->auth->clientSecret
        );

        $bTransaction = $this->auth->generateBTransaction();

        $response = $this->client->http()->get("/caas/v1.0/agreements/{$agreementId}/users", [
            'headers' => [
                'B-Option' => 0,
                'B-Transaction' => $bTransaction,
                'B-Application' => $this->client->bApplication,
                'Authorization' => 'Bearer ' . $accessToken['access_token'],
            ],
        ]);

        $payload = json_decode($response->getBody()->getContents(), true);

        $payload['data'] = $this->signature->decrypt($payload['data']);

        return $payload;
    }

    /**
     * Eliminación de usuarios de un contrato de cobranza domiciliada
     *
     * Se aceptan de 1 a 100 usuarios por solicitud
     *
     * @param string $agreementId
     * @param array $userIds
     * @return void
     * @throws \Exception
     */
    public function delete(int $agreementId, array $userIds): array
    {
        $accessToken = $this->auth->generateToken(
            $this->auth->clientId,
            $this->auth->clientSecret
        );

        $bTransaction = $this->auth->generateBTransaction();

        $response = $this->client->http()->delete("/caas/v1.0/agreements/{$agreementId}/users", [
            'headers' => [
                'B-Option' => 0,
                'B-Transaction' => $bTransaction,
                'B-Application' => $this->client->bApplication,
                'Authorization' => 'Bearer ' . $accessToken['access_token'],
            ],
            'json' => ['data' => $this->signature->sign($userIds)]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Actualiza usuarios en un contrato de cobranza domiciliada
     *
     * Se aceptan de 1 a 100 usuarios por solicitud.
     *
     * @param string $agreementId
     * @param array $userUpdateRequests
     * @return array
     * @throws \Exception
     */
    public function update(int $agreementId, array $userUpdateRequests): array
    {
        $accessToken = $this->auth->generateToken(
            $this->auth->clientId,
            $this->auth->clientSecret
        );

        $bTransaction = $this->auth->generateBTransaction();

        $response = $this->client->http()->patch("/caas/v1.0/agreements/{$agreementId}/users", [
            'headers' => [
                'B-Option' => 0,
                'B-Transaction' => $bTransaction,
                'B-Application' => $this->client->bApplication,
                'Authorization' => 'Bearer ' . $accessToken['access_token'],
            ],
            'json' => ['data' => $this->signature->sign($userUpdateRequests)]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}
