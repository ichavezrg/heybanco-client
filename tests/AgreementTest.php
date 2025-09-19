<?php

use Ichavezrg\HeyBancoClient\Auth;
use Ichavezrg\HeyBancoClient\Caas\Agreement;
use Ichavezrg\HeyBancoClient\Client;
use Ichavezrg\HeyBancoClient\Signature;
use PHPUnit\Framework\TestCase;

class AgreementTest extends TestCase
{
    private Client $client;
    private Signature $signature;
    private Agreement $agreement;
    private string $contractNumber;

    public function setUp(): void
    {
        parent::setUp();

        $bApplication = '845b7687-3886-4bb4-be1c-33e45a6c3d34';
        $this->contractNumber = '220914510015';

        $this->client = new Client(
            host: 'https://sbox-api-tech.hey.inc',
            bApplication: $bApplication,
            mtlsKeystorePath: 'tests/certs/Client_KeyStore_mTLS.p12',
            mtlsKeystorePassword: 'gOxH0cnofEL7wE/lH30aof0++2mrv1jHkoBAvOm3PUQ='
        );

        $this->signature = new Signature(
            bApplication: $bApplication,
            p12CertificatePath: 'tests/certs/Client_KeyStore_mTLS.p12',
            p12CertificatePassword: 'gOxH0cnofEL7wE/lH30aof0++2mrv1jHkoBAvOm3PUQ=',
            privateKeyPath: 'tests/certs/Client_private_key_in_pem.pem',
            privateKeyPhrase: '',
            publicServerKeyPath: 'tests/certs/Server_PublicKey_JWE.pem',
        );

        $this->agreement = new Agreement(
            $this->client,
            new Auth($this->client, 'c78ee0f5-c521-4896-84a0-4ba13ecce4dd', '7iL6uCS5sC02sySo8qyaCQbVXdodcFB7'),
            $this->signature
        );
    }

    public function testGetAgreements(): void
    {
        $agreements = $this->agreement->find(
            $this->contractNumber,
            'c78ee0f5-c521-4896-84a0-4ba13ecce4dd',
            '7iL6uCS5sC02sySo8qyaCQbVXdodcFB7'
        );

        $this->assertNotEmpty($agreements);
    }

    public function testHealthCheck(): void
    {
        $healthCheck = $this->agreement->healthCheck();
        $this->assertNotEmpty($healthCheck);
    }

    public function testGetTransactions(): void
    {
        $transactions = $this->agreement->getTransactions(
            62,
            new \DateTimeImmutable("2025-09-10"),
            new \DateTimeImmutable("2025-09-15"),
            1,
            1000
        );

        print_r($transactions);
        exit;

        $this->assertIsArray($transactions);
    }
}
