<?php

use Ichavezrg\HeyBancoClient\Client;
use GuzzleHttp\Client as HttpClient;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    public function testClient(): void
    {
        $client = new Client(
            'https://sbox-api-tech.hey.inc',
            '845b7687-3886-4bb4-be1c-33e45a6c3d34',
            'tests/certs/cert.pem',
            'tests/certs/key.pem',
            'gOxH0cnofEL7wE/lH30aof0++2mrv1jHkoBAvOm3PUQ='
        );

        $this->assertInstanceOf(Client::class, $client);
        $this->assertInstanceOf(HttpClient::class, $client->http());
    }
}
