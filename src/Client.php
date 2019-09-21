<?php

declare(strict_types=1);

namespace MNIB\UrgentCargus;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use GuzzleHttp\Exception\GuzzleException;
use MNIB\UrgentCargus\Exception\ClientException as UrgentCargusClientException;
use MNIB\UrgentCargus\Exception\InvalidSubscriptionException;
use function GuzzleHttp\json_decode;

class Client
{
    /** Library version */
    public const VERSION = '0.9.2';

    /** Default API Uri */
    public const API_URI = 'https://urgentcargus.azure-api.net/api/';

    /** @var string Subscription Key */
    private $apiKey;

    /** @var HttpClient */
    private $httpClient;

    /**
     * Set subscription key and uri for the UrgentCargus API.
     *
     * @param string $apiKey
     * @param string $apiUri
     */
    public function __construct(string $apiKey, ?string $apiUri = null)
    {
        if ($apiKey === '') {
            throw new InvalidSubscriptionException('The UrgentCargus API needs a subscription key.');
        }

        $this->apiKey = $apiKey;
        $baseUri = $apiUri !== null && $apiUri !== '' ? $apiUri : self::API_URI;

        $this->httpClient = new HttpClient([
            'base_uri' => $baseUri,
            'timeout' => 60,
            'allow_redirects' => false,
            'headers' => [
                'User-Agent' => 'UrgentCargusAPI-PHP (Version ' . self::VERSION . ')',
                'Content-Type' => 'application/json',
                'Accept-Charset' => 'utf-8',
            ],
        ]);
    }

    /**
     * Execute the request to the API.
     *
     * @param string      $method
     * @param string      $endpoint
     * @param mixed[]     $params
     * @param string|null $token
     *
     * @throws UrgentCargusClientException
     * @throws GuzzleException
     *
     * @return mixed
     */
    public function request(string $method, string $endpoint, array $params = [], ?string $token = null)
    {
        $headers = [
            'Ocp-Apim-Subscription-Key' => $this->apiKey,
        ];
        if ($token) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        try {
            $response = $this->httpClient->request($method, $endpoint, [
                'headers' => $headers,
                'json' => $params,
            ]);

            $contents = (string)$response->getBody();
        } catch (GuzzleClientException $exception) {
            throw UrgentCargusClientException::fromException($exception);
        }

        return $contents !== '' ? json_decode($contents, true) : null;
    }

    /**
     * Shorthand for GET request.
     *
     * @param string      $endpoint
     * @param mixed[]     $params
     * @param string|null $token
     *
     * @throws GuzzleException
     *
     * @return mixed
     */
    public function get(string $endpoint, array $params = [], ?string $token = null)
    {
        return $this->request('GET', $endpoint, $params, $token);
    }

    /**
     * Shorthand for POST request.
     *
     * @param string      $endpoint
     * @param mixed[]     $params
     * @param string|null $token
     *
     * @throws GuzzleException
     *
     * @return mixed
     */
    public function post(string $endpoint, array $params = [], ?string $token = null)
    {
        return $this->request('POST', $endpoint, $params, $token);
    }

    /**
     * Shorthand for PUT request.
     *
     * @param string      $endpoint
     * @param mixed[]     $params
     * @param string|null $token
     *
     * @throws GuzzleException
     *
     * @return mixed
     */
    public function put(string $endpoint, array $params = [], ?string $token = null)
    {
        return $this->request('PUT', $endpoint, $params, $token);
    }

    /**
     * Shorthand for DELETE request.
     *
     * @param string      $endpoint
     * @param mixed[]     $params
     * @param string|null $token
     *
     * @throws GuzzleException
     *
     * @return mixed
     */
    public function delete(string $endpoint, array $params = [], ?string $token = null)
    {
        return $this->request('DELETE', $endpoint, $params, $token);
    }

    /**
     * Get token from service.
     *
     * @param string $username
     * @param string $password
     *
     * @throws GuzzleException
     *
     * @return string
     */
    public function getToken(string $username, string $password): string
    {
        return $this->post('LoginUser', [
            'UserName' => $username,
            'Password' => $password,
        ]);
    }
}
