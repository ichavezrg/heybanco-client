<?php

use Ichavezrg\HeyBancoClient\Auth;
use Ichavezrg\HeyBancoClient\Caas\Collection;
use Ichavezrg\HeyBancoClient\Caas\CollectionValidity;
use Ichavezrg\HeyBancoClient\Caas\Misc\VerificationCode;
use Ichavezrg\HeyBancoClient\Caas\Periodicity;
use Ichavezrg\HeyBancoClient\Client;
use Ichavezrg\HeyBancoClient\Signature;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    private Client $client;
    private Signature $signature;
    private Collection $collection;
    private Auth $auth;
    private VerificationCode $verificationCode;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = new Client(
            'https://sbox-api-tech.hey.inc',
            '845b7687-3886-4bb4-be1c-33e45a6c3d34',
            'tests/certs/Client_KeyStore_mTLS.p12',
            'gOxH0cnofEL7wE/lH30aof0++2mrv1jHkoBAvOm3PUQ=',
            true
        );

        $this->auth = new Auth(
            $this->client,
            'c78ee0f5-c521-4896-84a0-4ba13ecce4dd',
            '7iL6uCS5sC02sySo8qyaCQbVXdodcFB7'
        );

        $this->signature = new Signature(
            '845b7687-3886-4bb4-be1c-33e45a6c3d34',
            'tests/certs/Client_KeyStore_mTLS.p12',
            'gOxH0cnofEL7wE/lH30aof0++2mrv1jHkoBAvOm3PUQ=',
            'tests/certs/Client_private_key_in_pem.pem',
            '',
            'tests/certs/Server_PublicKey_JWE.pem'
        );

        $this->verificationCode = new VerificationCode(
            $this->client,
            $this->auth,
            $this->signature
        );

        $this->collection = new Collection(
            $this->client,
            $this->auth,
            $this->signature
        );
    }

    public function testFind(): void
    {
        $collection = $this->collection->find(62, 1, 10, new DateTimeImmutable('2025-09-12'), new DateTimeImmutable('2025-09-15'));
        print_r($collection);
        exit;
        $this->assertNotEmpty($collection["data"]);
    }

    public function testCreate(): void
    {
        $verificationCode = $this->verificationCode->request();

        $collection = $this->collection->create(
            62,
            $verificationCode["authentication-code"],
            144,
            '160000214847953',
            100,
            Periodicity::MONTHLY,
            new DateTimeImmutable('2025-09-15'),
            CollectionValidity::unlimited()
        );
        print_r($collection);
        exit;
        $this->assertNotEmpty($collection["data"]);
    }

    public function testCancel(): void
    {
        $collection = $this->collection->cancel(62, [["collectionId" => 14105]]);
        print_r($collection);
        exit;
    }
}
