<?php

namespace Ichavez\HeyBancoClient\Caas;

use Ichavez\HeyBancoClient\Auth;
use Ichavez\HeyBancoClient\Client;

class User
{
    public function __construct(
        private readonly Client $client,
        private readonly Auth $auth
    ) {
    }

    /**
     * Consulta a un usuario de un contrato de cobranza domiciliada.
     *
     * @param string $agreementId
     * @param string $userId
     * @return array
     * @throws \Exception
     */
    public function show(string $agreementId, string $userId): array
    {
        throw new \Exception("Not implemented");
    }

    /**
     * Lista los usuarios registrados en un contrato de cobranza domiciliada.
     *
     * @param string $agreementId
     * @return array
     * @throws \Exception
     */
    public function find(string $agreementId): array
    {
        throw new \Exception("Not implemented");
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
    public function delete(string $agreementId, array $userIds): void
    {
        throw new \Exception("Not implemented");
    }

    /**
     * Actualiza usuarios en un contrato de cobranza domiciliada
     *
     * Se aceptan de 1 a 100 usuarios por solicitud.
     *
     * @param string $agreementId
     * @param array $userUpdateRequests
     * @return void
     * @throws \Exception
     */
    public function update(string $agreementId, array $userUpdateRequests): void
    {
        throw new \Exception("Not implemented");
    }
}
