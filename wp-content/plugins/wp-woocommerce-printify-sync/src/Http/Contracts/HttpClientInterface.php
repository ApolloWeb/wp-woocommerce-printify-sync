<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Http\Contracts;

interface HttpClientInterface {
    public function request(string $url, string $method = 'GET', array $options = []): array;
}
