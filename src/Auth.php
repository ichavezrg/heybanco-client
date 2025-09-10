<?php

namespace Ichavez\HeyBancoClient;

class Auth
{
    public function __construct(private Client $client) {}

    /**
     * array{access_token: string, refresh_expires_in: int, expires_in: int, token_type: string, scope: string, id_token: string, scope: string}
     */
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
