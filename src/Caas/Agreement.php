<?php

namespace Ichavez\HeyBancoClient\Caas;

use GuzzleHttp\Exception\GuzzleException;
use Ichavez\HeyBancoClient\Auth;
use Ichavez\HeyBancoClient\Client;
use Ichavez\HeyBancoClient\Signature;

class Agreement
{
    public function __construct(
        private readonly Client $client,
        private readonly Auth $auth,
        private readonly Signature $signature
    ) {
    }

    /**
     * Verificar el estado de al API
     *
     * Este endpoint se utiliza para realizar un "health check" de la API. Al acceder a esta ruta utilizando el
     * metodo OPTIONS, el servidor responderá con un código de estado 200 si la API está funcionando correctamente.
     * Si la API no está disponible o está experimentando problemas, se devolverá un código de estado 503.
     *
     * @return array
     * @throws \Exception
     */
    public function healthCheck(): array
    {
        throw new \Exception("Not implemented");
    }

    /**
     * Consulta información del contrato de cobranza domiciliada por número de cuenta.
     *
     * @param string $accountNumber
     * @param string $bTransaction
     * @param string $clientId
     * @param string $clientSecret
     * @return mixed
     * @throws GuzzleException
     */
    public function getAgreements(string $accountNumber, string $bTransaction, string $clientId, string $clientSecret)
    {
        $accessToken = $this->auth->generateToken(
            $clientId,
            $clientSecret
        );

        $response = $this->client->http()->get('/caas/v1.0/agreements', [
            'headers' => [
                'B-Option' => 0,
                'B-Transaction' => $bTransaction,
                'Authorization' => 'Bearer ' . $accessToken['access_token'],
            ],
            'query' => [
                'accountNumber' => $accountNumber
            ]
        ]);

        $responseArray = json_decode($response->getBody()->getContents(), true);

        return $this->signature->decrypt($responseArray["data"]);
    }

    /**
     * Consulta el estatus de cargos realizados por domiciliación en un periodo de tiempo.
     *
     * @param string $agreementId
     * @return array
     * @throws \Exception
     */
    public function getTransactions(string $agreementId): array
    {
        throw new \Exception("Not implemented");
    }
}
