<?php

namespace ApolloWeb\WPWooCommercePrintifySync\API;

use ApolloWeb\WPWooCommercePrintifySync\Helpers\ApiRequestHelper;

class PostmanApi
{
    private $apiKey;
    private $apiUrl = 'https://api.getpostman.com/';

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    private function getHeaders()
    {
        return [
            'X-Api-Key' => $this->apiKey,
            'Content-Type' => 'application/json'
        ];
    }

    public function getMockServers()
    {
        $url = $this->apiUrl . 'mockservers';
        return ApiRequestHelper::getRequest($url, $this->getHeaders());
    }

    public function updateEndpoint($mockServerId)
    {
        // Update the endpoint based on the selected mock server
        $url = $this->apiUrl . 'mockservers/' . $mockServerId;
        $response = ApiRequestHelper::getRequest($url, $this->getHeaders());
        if (is_wp_error($response)) {
            return $response;
        }
        $endpoint = $response['mock']['url'];
        update_option('postman_mock_endpoint', $endpoint);
        return $endpoint;
    }

    public function uploadLiveData($mockServerId, $liveData)
    {
        $url = $this->apiUrl . 'mockservers/' . $mockServerId . '/data';
        return ApiRequestHelper::postRequest($url, $this->getHeaders(), $liveData);
    }

    // Other methods...
}