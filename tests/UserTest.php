<?php

use Ichavezrg\HeyBancoClient\Auth;
use Ichavezrg\HeyBancoClient\Caas\Agreement;
use Ichavezrg\HeyBancoClient\Caas\Misc\VerificationCode;
use Ichavezrg\HeyBancoClient\Caas\User;
use Ichavezrg\HeyBancoClient\Client;
use Ichavezrg\HeyBancoClient\Signature;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private Client $client;
    private Signature $signature;
    private User $user;
    private int $agreementId;
    private VerificationCode $verificationCode;
    private Auth $auth;

    public function setUp(): void
    {
        parent::setUp();

        $bApplication = '845b7687-3886-4bb4-be1c-33e45a6c3d34';
        $this->agreementId = 62;

        $this->client = new Client(
            host: 'https://sbox-api-tech.hey.inc',
            bApplication: $bApplication,
            certPath: 'tests/certs/cert.pem',
            keyPath: 'tests/certs/key.pem',
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

        $this->user = new User(
            $this->client,
            $this->auth,
            $this->signature
        );

        $this->verificationCode = new VerificationCode(
            $this->client,
            $this->auth,
            $this->signature
        );
    }

    public function testCreateUser(): void
    {
        $verificationCode = $this->verificationCode->request();

        $user = $this->user->create(
            $this->agreementId,
            $verificationCode["authentication-code"],
            [
                [
                    'fullName' => 'Ivan Ernesto Chavez Sanchez',
                    'alias' => 'Prestamo RedGirasol',
                    'rfc' => 'CASI900101KL0',
                    'accountNumber' => '002760902539634082',
                    'accountType' => 'CLABE',
                    'email' => 'ichavez9001@gmail.com',
                    'minimumAmount' => 1,
                    'maximumAmount' => 1000
                ]
            ]
        );

        print_r($user);
        exit;
    }

    public function testShowUser(): void
    {
        $user = $this->user->show($this->agreementId, 144);
        print_r($user);
        exit;
    }

    public function testDeleteUser(): void
    {
        $user = $this->user->delete($this->agreementId, [["userId" => 144]]);
        print_r($user);
        exit;
    }

    public function testFindUsers(): void
    {
        $users = $this->user->find($this->agreementId);
        print_r($users);
        exit;
    }

    public function testUpdateUser(): void
    {
        $user = $this->user->update($this->agreementId, [["userId" => 144, "alias" => "Credito RedGirasol"]]);
        print_r($user);
        exit;
    }
}
