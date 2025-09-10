<?php

namespace Ichavez\HeyBancoClient;

use GuzzleHttp\Client as HttpClient;

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
        public readonly string $mtlsKeystorePath,
        public readonly string $mtlsKeystorePassword,
        public readonly bool $debug = false,
    ) {
        if (!file_exists($this->mtlsKeystorePath)) {
            throw new \Exception('MTLs keystore file not found');
        }

        $this->httpClient = new HttpClient([
            'base_uri' => $this->host,
            'debug' => $this->debug,
            'headers' => [
                'B-Application' => $this->bApplication,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Accept-Charset' => 'UTF-8',
            ],
            'curl' => [
                CURLOPT_SSLCERT => $this->mtlsKeystorePath,
                CURLOPT_SSLCERTPASSWD => $this->mtlsKeystorePassword,
                CURLOPT_SSLCERTTYPE => 'P12',
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ],
        ]);
    }

    public function http(): HttpClient
    {
        return $this->httpClient;
    }
}
