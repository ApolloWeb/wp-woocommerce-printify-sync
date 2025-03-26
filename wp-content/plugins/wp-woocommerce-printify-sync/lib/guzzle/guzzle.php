<?php
/**
 * Minimal Guzzle HTTP client implementation
 */

namespace GuzzleHttp;

class Client {
    private $options;

    public function __construct(array $options = []) {
        $this->options = array_merge([
            'base_uri' => '',
            'timeout' => 30,
            'headers' => [],
        ], $options);
    }

    public function request(string $method, string $uri, array $options = []) {
        $headers = array_merge($this->options['headers'], $options['headers'] ?? []);
        $url = $this->options['base_uri'] . $uri;
        
        $context = stream_context_create([
            'http' => [
                'method' => strtoupper($method),
                'header' => $this->formatHeaders($headers),
                'content' => $options['json'] ?? $options['body'] ?? null,
                'timeout' => $this->options['timeout'],
            ],
        ]);

        $response = file_get_contents($url, false, $context);
        if ($response === false) {
            throw new \RuntimeException('Request failed: ' . error_get_last()['message']);
        }

        return new Response($response, $http_response_header);
    }

    private function formatHeaders(array $headers): string {
        $formatted = [];
        foreach ($headers as $name => $value) {
            $formatted[] = "$name: $value";
        }
        return implode("\r\n", $formatted);
    }
}

class Response {
    private $body;
    private $headers;
    private $statusCode;

    public function __construct(string $body, array $headers) {
        $this->body = $body;
        $this->headers = $headers;
        $this->statusCode = $this->extractStatusCode($headers[0]);
    }

    public function getBody(): string {
        return $this->body;
    }

    public function getStatusCode(): int {
        return $this->statusCode;
    }

    private function extractStatusCode(string $statusLine): int {
        preg_match('/HTTP\/\d\.\d\s+(\d+)/', $statusLine, $matches);
        return (int) ($matches[1] ?? 500);
    }
}