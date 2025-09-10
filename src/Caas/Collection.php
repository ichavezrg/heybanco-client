<?php

namespace Ichavez\HeyBancoClient\Caas;

use DateTimeImmutable;
use Ichavez\HeyBancoClient\Auth;
use Ichavez\HeyBancoClient\Client;

class Collection
{
    public function __construct(
        private readonly Client $client,
        private readonly Auth $auth
    ) {
    }

    /**
     * Consulta domiciliación de cargos previamente programadas.
     *
     * @param string $agreementId
     * @return array
     * @throws \Exception
     */
    public function find(string $agreementId): array
    {
        throw new \Exception("method not implemented");
    }

    /**
     * Programa cargos de domiciliación.
     *
     * @param string $agreementId
     * @param string $userId
     * @param string $reference
     * @param float $amount
     * @param string $periodicity
     * @param DateTimeImmutable $firstCollectionDate
     * @param array $collectionValidity
     * @return array
     * @throws \Exception
     */
    public function create(
        string $agreementId,
        string $userId,
        string $reference,
        float $amount,
        string $periodicity,
        DateTimeImmutable $firstCollectionDate,
        array $collectionValidity
    ): array {
        throw new \Exception("method not implemented");
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
        throw new \Exception("method not implemented");
    }
}
