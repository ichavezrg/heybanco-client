<?php

namespace Ichavez\HeyBancoClient\Tests;

use Ichavez\HeyBancoClient\Auth;
use Ichavez\HeyBancoClient\Client;
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    public function testGenerateToken(): void
    {
        $auth = new Auth(
            new Client(
                'https://sbox-api-tech.hey.inc',
                '845b7687-3886-4bb4-be1c-33e45a6c3d34',
                'tests/certs/Client_KeyStore_mTLS.p12',
                'gOxH0cnofEL7wE/lH30aof0++2mrv1jHkoBAvOm3PUQ='
            )
        );

        $token = $auth->generateToken(
            'c78ee0f5-c521-4896-84a0-4ba13ecce4dd',
            '7iL6uCS5sC02sySo8qyaCQbVXdodcFB7'
        );

        $this->assertArrayHasKey('access_token', $token);
        $this->assertArrayHasKey('refresh_expires_in', $token);
        $this->assertArrayHasKey('expires_in', $token);
        $this->assertArrayHasKey('token_type', $token);
        $this->assertArrayHasKey('scope', $token);
        $this->assertArrayHasKey('id_token', $token);
        $this->assertArrayHasKey('scope', $token);
    }
}
