<?php

namespace Ichavezrg\HeyBancoClient;

class Auth
{
    public function __construct(private Client $client, public readonly string $clientId, public readonly string $clientSecret) {}


    /**
     * array{access_token: string, refresh_expires_in: int, expires_in: int, token_type: string, scope: string, id_token: string, scope: string}
     */
    public function generateToken(): array
    {
        $response = $this->client->http()->post('/auth/v1/oidc/token', [
            'form_params' => [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => "client_credentials",
                'scope' => "openid",
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}
