<?php

namespace ApolloWeb\WPWooCommercePrintifySync\API\Interfaces;

interface ConnectionTestInterface
{
    /**
     * Test the connection to the API
     *
     * @return bool True if connected successfully
     */
    public function testConnection(): bool;
}
