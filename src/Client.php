<?php

namespace Ichavezrg\HeyBancoClient;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\HandlerStack;

class Client
{
    private HttpClient $httpClient;

    /**
     * @param string $host
     * @param string $bApplication
     * @param string $mtlsKeystorePath
     * @param string $mtlsKeystorePassword
     * @param bool $debug
     * @throws \Exception
     */
    public function __construct(
        public readonly string $host,
        public readonly string $bApplication,
        public readonly string $certPath,
        public readonly string $keyPath,
        public readonly string $mtlsKeystorePassword,
        public readonly bool $debug = false,
        public readonly HandlerStack|null $handlerStack = null,
    ) {
        $this->httpClient = new HttpClient([
            'base_uri' => $this->host,
            'debug' => $this->debug,
            'handler' => $this->handlerStack,
            'headers' => [
                'B-Application' => $this->bApplication,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Accept-Charset' => 'UTF-8',
            ],
            'curl' => [
                \CURLOPT_SSLCERT => $this->certPath,
                \CURLOPT_SSLKEY => $this->keyPath,
                \CURLOPT_SSLCERTPASSWD => $this->mtlsKeystorePassword,
                \CURLOPT_SSL_VERIFYPEER => true,
                \CURLOPT_SSL_VERIFYHOST => 2,
            ],
        ]);
    }

    public function http(): HttpClient
    {
        return $this->httpClient;
    }
}
