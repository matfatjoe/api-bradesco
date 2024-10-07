<?php

namespace Matfatjoe\ApiBradesco;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Matfatjoe\ApiBradesco\Exceptions\ForbiddenException;
use Matfatjoe\ApiBradesco\Exceptions\InvalidRequestException;
use Matfatjoe\ApiBradesco\Exceptions\UnauthorizedException;

class BankingBradesco
{
    const HTTP_EXCEPTION_TYPES = [
        UnauthorizedException::HTTP_STATUS_CODE => UnauthorizedException::class,
        ForbiddenException::HTTP_STATUS_CODE => ForbiddenException::class,
        InvalidRequestException::HTTP_STATUS_CODE => InvalidRequestException::class,
    ];

    const PRODUCTION_ENV = 1;
    const HOMOLOGATION_ENV = 2;

    private $config;
    private $token;

    private $uriToken;
    private $clientToken;
    private $optionsRequest;

    function __construct($config)
    {
        $this->config = $config;
        $this->uriToken = 'https://proxy.api.prebanco.com.br';
        // if ($config['endpoints'] == 1) {
        // } else {
        // }
        $this->clientToken = new Client([
            'base_uri' => $this->uriToken,
        ]);
        // $this->clientCobranca = new Client([
        //     'base_uri' => $this->uriCobranca,
        // ]);

        $this->optionsRequest = [
            'headers' => [
                'Accept' => 'application/x-www-form-urlencoded'
            ]
        ];
        if (isset($this->config['token'])) {
            if ($this->config['token'] != '') {
                $this->setToken($this->config['token']);
            } else {
                $this->gerarToken();
            }
        }
    }

    public function setClientToken(Client $client)
    {
        $this->clientToken = $client;
    }
    public function setToken(string $token)
    {
        $this->token = $token;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function gerarToken()
    {
        try {
            $jwt = $this->generateJWT();
            $hashAndSignJwt = $this->createSignedHash($jwt);
            $jws = "{$jwt}.{$hashAndSignJwt}";

            $uri = "/auth/server/v1.1/token";
            $options = $this->optionsRequest;
            $options['form_params'] = [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jws
            ];
            $response = $this->makeRequest(
                $this->clientToken,
                'POST',
                $uri,
                $options,
                "Falha ao gerar Token"
            );
            $response = $response['response'];

            $this->token = $response->access_token;
            $this->optionsRequest['headers']['Authorization'] = "Bearer {$this->token}";
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function generateJWT($alg = 'RS256')
    {
        $header = json_encode([
            'alg' => $alg,
            'typ' => 'JWT'
        ]);
        $iat = $this->getIat();

        $payload = json_encode([
            'aud' => 'https://proxy.api.prebanco.com.br/auth/server/v1.1/token',
            'sub' => $this->config['client_key'],
            'iat' => $iat,
            'exp' => $iat + 3600,
            'jti' => intval($iat . '000'),
            'ver' => '1.1'
        ]);

        return base64_encode($header) . '.' . base64_encode($payload);
    }

    public function createSignedHash($jwt)
    {
        $privateKey = file_get_contents($this->config['cert_path']);
        $privateKeyId = openssl_pkey_get_private($privateKey);
        openssl_sign($jwt, $signature, $privateKeyId, OPENSSL_ALGO_SHA256);

        openssl_free_key($privateKeyId);
        $base64Signature = base64_encode($signature);
        $urlSafeSignature = strtr($base64Signature, '+/', '-_');
        $finalSignature = rtrim($urlSafeSignature, '=');

        return $finalSignature;
    }

    private function getIat(): int
    {
        if ($this->config['endpoints'] == self::HOMOLOGATION_ENV) {
            return 1111111111;
        }
        return time();
    }

    private function makeRequest(Client $client, $method, $uri, $options, $errorMessage)
    {
        try {
            $this->getToken();

            $response = $client->request($method, $uri, $options);
            $statusCode = $response->getStatusCode();
            $result = json_decode($response->getBody()->getContents());
            return array('status' => $statusCode, 'response' => $result);
        } catch (ClientException $e) {
            $statusCode = $e->getResponse()->getStatusCode();
            $requestParameters = $e->getRequest();
            $bodyContent = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $e->getResponse()->getBody()->getContents());
            $bodyContent = json_decode("{$bodyContent}");

            if (isset(self::HTTP_EXCEPTION_TYPES[$statusCode])) {
                $exceptionClass = self::HTTP_EXCEPTION_TYPES[$statusCode];
                $message = $bodyContent->message;
                if (in_array($bodyContent->code, [123, 104])) {
                    $message = 'Chave de acesso inválida.';
                }
                $exception = new $exceptionClass($message);
                $exception->setRequestParameters($requestParameters);
                $exception->setBodyContent($bodyContent);
            } else {
                $exception = $e;
            }
            throw $exception;
        } catch (Exception $e) {
            $response = $e->getMessage();
            return ['error' => "{$errorMessage}: {$response}"];
        }
    }
}
