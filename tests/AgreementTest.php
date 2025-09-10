<?php

namespace Ichavez\HeyBancoClient\Tests;

use Ichavez\HeyBancoClient\Auth;
use Ichavez\HeyBancoClient\Caas\Agreement;
use Ichavez\HeyBancoClient\Client;
use Ichavez\HeyBancoClient\Signature;
use PHPUnit\Framework\TestCase;

class AgreementTest extends TestCase
{
    public function testGetAgreements()
    {
        $bApplication = '845b7687-3886-4bb4-be1c-33e45a6c3d34';

        $client = new Client(
            host: 'https://sbox-api-tech.hey.inc',
            bApplication: $bApplication,
            mtlsKeystorePath: 'tests/Client_KeyStore_mTLS.p12',
            mtlsKeystorePassword: 'gOxH0cnofEL7wE/lH30aof0++2mrv1jHkoBAvOm3PUQ='
        );

        $signature = new Signature(
            bApplication: $bApplication,
            pemPrivateKeyPath: 'tests/Client_private_key_in_pem.pem',
            pemServerPublicKeyPath: 'tests/Server_PublicKey_JWE.pem',
        );

        $agreement = new Agreement(
            $client,
            new Auth($client),
            $signature
        );

        $agreements = $agreement->getAgreements(
            '220914510015',
            (string)random_int(10000, 99999),
            'c78ee0f5-c521-4896-84a0-4ba13ecce4dd',
            '7iL6uCS5sC02sySo8qyaCQbVXdodcFB7'
        );

        print_r($agreements);
    }
}
