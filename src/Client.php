<?php

declare(strict_types=1);

namespace MNIB\UrgentCargus;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use MNIB\UrgentCargus\Exception\ClientException as UrgentCargusClientException;
use MNIB\UrgentCargus\Exception\InvalidSubscriptionException;
use MNIB\UrgentCargus\Exception\InvalidTokenException;
use function GuzzleHttp\json_decode;
use function sprintf;
use function trigger_error;
use const E_USER_DEPRECATED;

class Client
{
    /** Library version */
    public const VERSION = '0.9.11';

    /** Default API Uri */
    public const API_URI = 'https://urgentcargus.azure-api.net/api/';

    /** @var string Subscription Key */
    private $apiKey;

    /** @var HttpClient */
    private $httpClient;

    /** @var string|null */
    private $accessToken;

    /**
     * Set subscription key and uri for the UrgentCargus API.
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
                'User-Agent' => 'UrgentCargusAPI-PHP (v' . self::VERSION . ')',
                'Content-Type' => 'application/json',
                'Accept-Charset' => 'utf-8',
            ],
        ]);
    }

    /**
     * Execute the request to the API.
     *
     * @param mixed[] $params
     *
     * @throws UrgentCargusClientException
     *
     * @return mixed
     */
    public function request(string $method, string $endpoint, array $params = [], ?string $token = null)
    {
        $headers = [
            'Ocp-Apim-Subscription-Key' => $this->apiKey,
        ];

        if ($token) {
            @trigger_error(sprintf('Calling "%s()" with the $token argument is deprecated since 0.9.3.', __METHOD__), E_USER_DEPRECATED);

            $headers['Authorization'] = 'Bearer ' . $token;
        } elseif ($this->accessToken) {
            $headers['Authorization'] = 'Bearer ' . $this->accessToken;
        }

        try {
            $response = $this->httpClient->request($method, $endpoint, [
                'headers' => $headers,
                'json' => $params,
            ]);

            $contents = (string)$response->getBody();
        } catch (GuzzleException $exception) {
            throw UrgentCargusClientException::fromException($exception);
        }

        return $contents !== '' ? json_decode($contents, true) : null;
    }

    /**
     * Shorthand for GET request.
     *
     * @param mixed[] $params
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
     * @param mixed[] $params
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
     * @param mixed[] $params
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
     * @param mixed[] $params
     *
     * @return mixed
     */
    public function delete(string $endpoint, array $params = [], ?string $token = null)
    {
        return $this->request('DELETE', $endpoint, $params, $token);
    }

    public function getToken(string $username, string $password): string
    {
        if ($this->accessToken === null) {
            $this->createAccessToken($username, $password);
        }

        return $this->accessToken;
    }

    public function setAccessToken(?string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    /**
     * Get token from service.
     */
    public function createAccessToken(string $username, string $password): void
    {
        $accessToken = $this->post('LoginUser', [
            'UserName' => $username,
            'Password' => $password,
        ]);

        if ($accessToken === null || $accessToken === '') {
            throw new InvalidTokenException('UrgentCargus API did not return a valid token.');
        }

        $this->setAccessToken($accessToken);
    }
}
