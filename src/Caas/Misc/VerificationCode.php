<?php

namespace Ichavezrg\HeyBancoClient\Caas\Misc;

use Ichavezrg\HeyBancoClient\Auth;
use Ichavezrg\HeyBancoClient\Client;
use Ichavezrg\HeyBancoClient\Signature;

class VerificationCode
{
    public function __construct(
        private readonly Client $client,
        private readonly Auth $auth,
        private readonly Signature $signature
    ) {}

    public function request(): array
    {
        $accessToken = $this->auth->generateToken(
            $this->auth->clientId,
            $this->auth->clientSecret
        );

        $bTransaction = "123456";

        $response = $this->client->http()->get("/misc/v1.0/verification-codes", [
            'headers' => [
                'B-Option' => 0,
                'B-Application' => $this->client->bApplication,
                'B-Transaction' => $bTransaction,
                'Authorization' => 'Bearer ' . $accessToken['access_token'],
            ],
        ]);

        $payload = json_decode($response->getBody()->getContents(), true);

        return $this->signature->decrypt($payload['data']);
    }
}
