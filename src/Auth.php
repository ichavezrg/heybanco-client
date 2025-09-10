<?php

namespace Ichavez\HeyBancoClient;

class Auth
{
    public function __construct(private Client $client) {}

    public function generateToken(string $clientId, string $clientSecret): array
    {
        $response = $this->client->http()->post('/auth/v1/oidc/token', [
            'form_params' => [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => "client_credentials",
                'scope' => "openid",
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}
