<?php

use Ichavezrg\HeyBancoClient\Auth;
use Ichavezrg\HeyBancoClient\Caas\Misc\Webhook;
use Ichavezrg\HeyBancoClient\Client;
use Ichavezrg\HeyBancoClient\Signature;
use PHPUnit\Framework\TestCase;

class WebhookTest extends TestCase
{
    private Client $client;
    private Signature $signature;
    private Auth $auth;
    private Webhook $webhook;

    public function setUp(): void
    {
        parent::setUp();

        $bApplication = '845b7687-3886-4bb4-be1c-33e45a6c3d34';

        $this->client = new Client(
            host: 'https://sbox-api-tech.hey.inc',
            bApplication: $bApplication,
            mtlsKeystorePath: 'tests/certs/Client_KeyStore_mTLS.p12',
            mtlsKeystorePassword: 'gOxH0cnofEL7wE/lH30aof0++2mrv1jHkoBAvOm3PUQ=',
            debug: false
        );

        $this->signature = new Signature(
            bApplication: $bApplication,
            p12CertificatePath: 'tests/certs/Client_KeyStore_mTLS.p12',
            p12CertificatePassword: 'gOxH0cnofEL7wE/lH30aof0++2mrv1jHkoBAvOm3PUQ=',
            privateKeyPath: 'tests/certs/Client_private_key_in_pem.pem',
            privateKeyPhrase: '',
            publicServerKeyPath: 'tests/certs/Server_PublicKey_JWE.pem',
        );

        $this->auth = new Auth($this->client, 'c78ee0f5-c521-4896-84a0-4ba13ecce4dd', '7iL6uCS5sC02sySo8qyaCQbVXdodcFB7');

        $this->webhook = new Webhook(
            $this->client,
            $this->auth,
            $this->signature
        );
    }

    public function testShowWebhooks(): void
    {
        $webhook = $this->webhook->findAll();
        print_r($webhook);
        exit;
    }

    public function testCreate(): void
    {
        $webhook = $this->webhook->create(
            "https://api.qa.redgirasol.com/webhooks/heybanco/authentication",
            "https://api.qa.redgirasol.com/webhooks/heybanco/notifications",
            "https://api.qa.redgirasol.com/webhooks/heybanco/authorization",
            [["id" => 6]]
        );
        print_r($webhook);
        exit;
    }

    public function testShowEvents(): void
    {
        $events = $this->webhook->showEvents();
        print_r($events);
        exit;
    }

    public function testDelete(): void
    {
        $webhook = $this->webhook->delete(101);
        print_r($webhook);
        exit;
    }
}
