<?php

use Ichavezrg\HeyBancoClient\Auth;
use Ichavezrg\HeyBancoClient\Caas\Agreement;
use Ichavezrg\HeyBancoClient\Client;
use Ichavezrg\HeyBancoClient\Signature;
use PHPUnit\Framework\TestCase;

class AgreementTest extends TestCase
{
    public function testGetAgreements(): void
    {
        $bApplication = '845b7687-3886-4bb4-be1c-33e45a6c3d34';

        $client = new Client(
            host: 'https://sbox-api-tech.hey.inc',
            bApplication: $bApplication,
            mtlsKeystorePath: 'tests/certs/Client_KeyStore_mTLS.p12',
            mtlsKeystorePassword: 'gOxH0cnofEL7wE/lH30aof0++2mrv1jHkoBAvOm3PUQ='
        );

        $signature = new Signature(
            bApplication: $bApplication,
            p12CertificatePath: 'tests/certs/Client_KeyStore_mTLS.p12',
            p12CertificatePassword: 'gOxH0cnofEL7wE/lH30aof0++2mrv1jHkoBAvOm3PUQ=',
            privateKeyPath: 'tests/certs/Client_private_key_in_pem.pem',
            privateKeyPhrase: '',
            publicServerKeyPath: 'tests/certs/Server_PublicKey_JWE.pem',
        );

        $agreement = new Agreement(
            $client,
            new Auth($client),
            $signature
        );

        $agreements = $agreement->find(
            '220914510015',
            (string)random_int(10000, 99999),
            'c78ee0f5-c521-4896-84a0-4ba13ecce4dd',
            '7iL6uCS5sC02sySo8qyaCQbVXdodcFB7'
        );

        $this->assertNotEmpty($agreements);
    }
}
