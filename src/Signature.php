<?php

namespace Ichavez\HeyBancoClient;

use Exception;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A256GCM;
use Jose\Component\Encryption\Algorithm\KeyEncryption\RSAOAEP256;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Encryption\JWEDecrypter;
use Jose\Component\Encryption\Serializer\JWESerializerManager;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Signature\Serializer\JWSSerializerManager;

class Signature
{
    private AlgorithmManager $keyEncryptionAlgorithmManager;
    private AlgorithmManager $contentEncryptionAlgorithmManager;

    public function __construct(
        public readonly string $bApplication,
        public readonly string $mtlsCertificatePath,
        public readonly string $mtlsCertificatePassword,
        public readonly string $privateKeyPath,
        private readonly string $privateKeyPhrase,
        public readonly string $publicServerKeyPath,
    ) {
        $this->keyEncryptionAlgorithmManager = new AlgorithmManager([new RSAOAEP256()]);
        $this->contentEncryptionAlgorithmManager = new AlgorithmManager([new A256GCM()]);
    }

    public function sign(array $payload): string
    {
        try {
            $algorithmManager = new AlgorithmManager([new RS256()]);
            $jwsBuilder = new JWSBuilder($algorithmManager);
            $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($encodedPayload === false) {
                throw new \RuntimeException('Error encoding payload');
            }

            $jws = $jwsBuilder
                ->create()
                ->withPayload($encodedPayload)
                ->addSignature($this->getJwsPrivateKey(), ['alg' => 'RS256'])
                ->build();

            $serializer = new CompactSerializer();
            $signedPayload = $serializer->serialize($jws, 0);

            $jweBuilder = new JWEBuilder(
                $this->keyEncryptionAlgorithmManager,
                $this->contentEncryptionAlgorithmManager
            );

            $jwe = $jweBuilder
                ->create()
                ->withPayload($signedPayload)
                ->withSharedProtectedHeader([
                    'alg' => 'RSA-OAEP-256',
                    'enc' => 'A256GCM',
                    'kid' => $this->bApplication
                ])
                ->addRecipient($this->getJwkPublicKey())
                ->build();

            $serializer = new \Jose\Component\Encryption\Serializer\CompactSerializer();
            return $serializer->serialize($jwe, 0);
        } catch (\Exception $e) {
            throw new \Exception('Signing error: ' . $e->getMessage());
        }
    }

    public function decrypt(string $sign): string
    {
        $jweDecrypter = new JWEDecrypter(
            $this->keyEncryptionAlgorithmManager,
            $this->contentEncryptionAlgorithmManager,
        );

        $serializerManager = new JWESerializerManager([new \Jose\Component\Encryption\Serializer\CompactSerializer()]);
        $jwe = $serializerManager->unserialize($sign);
        $privateKey = $this->getJwsPrivateKey();

        $recipients = $jwe->getRecipients();
        if (empty($recipients)) {
            throw new \Exception('No recipients found in JWE');
        }

        $success = $jweDecrypter->decryptUsingKey($jwe, $privateKey, 0);

        if (!$success) {
            throw new \Exception('Invalid decryption - JWE decryption failed');
        }


        $algorithmManager = new AlgorithmManager([new RS256()]);
        // We instantiate our JWS Verifier.
        $jwsVerifier = new JWSVerifier($algorithmManager);
        $serializerManager = new JWSSerializerManager([
            new CompactSerializer(),
        ]);

        $jws = $serializerManager->unserialize($jwe->getPayload());
        $isVerified = $jwsVerifier->verifyWithKey($jws, $this->getJwkPublicKey(), 0);

        if (!$isVerified) {
            throw new \Exception('Invalid signature');
        }

        $payload = $jws->getPayload();
        if ($payload === null) {
            throw new \Exception('Invalid payload');
        }

        return $payload;
    }

    private function getJwsPrivateKey(): JWK
    {
        return JWKFactory::createFromKeyFile(
            $this->privateKeyPath,
            $this->privateKeyPhrase
        );
    }

    private function getJwkPublicKey(): JWK
    {
        return JWKFactory::createFromKeyFile($this->publicServerKeyPath);
    }
}
