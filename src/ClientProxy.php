<?php

namespace Ichavezrg\HeyBancoClient;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Cliente HTTP que registra cada petición. Lo que escribe en el log pasa por
 * {@see LogSanitizer}: ni las credenciales, ni los tokens, ni las cabeceras de
 * autenticación llegan al log.
 */
class ClientProxy extends Client
{
    public function __construct(array $config = [], private readonly ?LoggerInterface $logger = null)
    {
        parent::__construct($config);
    }

    public function request(string $method, $uri = '', array $options = []): ResponseInterface
    {
        $response = parent::request($method, $uri, $options);
        $headers = $response->getHeaders();

        $this->logger?->info(LogSanitizer::text($method . ' : ' . $uri), [
            "request" => LogSanitizer::request($options),
            "response" => LogSanitizer::text($response->getBody()->getContents()),
            "headers" => LogSanitizer::headers($headers),
        ]);

        $response->getBody()->rewind();

        return $response;
    }
}
