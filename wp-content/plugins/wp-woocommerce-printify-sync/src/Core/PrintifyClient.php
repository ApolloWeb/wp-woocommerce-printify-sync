<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class PrintifyClient
{
    private Client $client;
    private array $rateLimits = [
        'remaining' => 100,
        'reset' => 0
    ];

    public function __construct(
        string $apiKey,
        int $maxRetries = 3,
        string $apiEndpoint = 'https://api.printify.com/v1/'
    ) {
        $stack = HandlerStack::create();
        $stack->push($this->retryMiddleware($maxRetries));
        $stack->push($this->rateLimitMiddleware());

        $this->client = new Client([
            'base_uri' => $apiEndpoint,
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'application/json',
            ],
            'handler' => $stack,
        ]);
    }

    public function get(string $endpoint, array $params = []): array
    {
        return $this->request('GET', $endpoint, $params);
    }

    public function post(string $endpoint, array $data): array
    {
        return $this->request('POST', $endpoint, ['json' => $data]);
    }

    public function put(string $endpoint, array $data): array
    {
        return $this->request('PUT', $endpoint, ['json' => $data]);
    }

    public function delete(string $endpoint): array
    {
        return $this->request('DELETE', $endpoint);
    }

    private function request(string $method, string $endpoint, array $options = []): array
    {
        try {
            $this->waitForRateLimit();
            $response = $this->client->request($method, $endpoint, $options);
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw $this->handleException($e);
        }
    }

    private function retryMiddleware(int $maxRetries): callable
    {
        return Middleware::retry(
            function (
                $retries,
                Request $request,
                ?Response $response = null,
                ?RequestException $exception = null
            ) {
                if ($retries >= $maxRetries) {
                    return false;
                }

                if ($response && $response->getStatusCode() === 429) {
                    return true;
                }

                if ($exception && $exception->getCode() >= 500) {
                    return true;
                }

                return false;
            },
            function ($retries) {
                return (1 << $retries) * 1000;
            }
        );
    }

    private function rateLimitMiddleware(): callable
    {
        return Middleware::mapResponse(function (Response $response) {
            $this->rateLimits['remaining'] = (int) $response->getHeaderLine('X-RateLimit-Remaining');
            $this->rateLimits['reset'] = (int) $response->getHeaderLine('X-RateLimit-Reset');
            return $response;
        });
    }

    private function waitForRateLimit(): void
    {
        if ($this->rateLimits['remaining'] === 0) {
            $sleepTime = $this->rateLimits['reset'] - time();
            if ($sleepTime > 0) {
                sleep($sleepTime);
            }
        }
    }

    private function handleException(GuzzleException $e): GuzzleException
    {
        if ($e instanceof RequestException && $e->hasResponse()) {
            $body = json_decode($e->getResponse()->getBody()->getContents(), true);
            $message = $body['message'] ?? $e->getMessage();
            error_log('Printify API Error: ' . $message);
        }
        return $e;
    }
}