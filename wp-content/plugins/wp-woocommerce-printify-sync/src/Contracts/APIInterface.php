<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Contracts;

interface APIInterface
{
    public function get(string $endpoint, array $params = []): array;
    public function post(string $endpoint, array $data = []): array;
    public function put(string $endpoint, array $data = []): array;
    public function delete(string $endpoint): array;
}