<?php

namespace Ichavezrg\HeyBancoClient;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class ClientProxy extends Client
{
    public function __construct(array $config = [], private readonly ?LoggerInterface $logger = null)
    {
        return parent::__construct($config);
    }

    public function request(string $method, $uri = '', array $options = []): ResponseInterface
    {
        $response = parent::request($method, $uri, $options);
        $headers = $response->getHeaders();

        $this->logger?->info($method . ' : ' . $uri, [
            "request" => $options,
            "response" => $response->getBody()->getContents(),
            "headers" => $headers,
        ]);

        return $response;
    }
}
