<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Api;

class CurrencyApi extends AbstractApi
{
    protected function initializeHeaders(): void
    {
        $this->headers = [
            'apikey' => $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
    }

    public function getExchangeRates(string $baseCurrency = 'USD'): array
    {
        return $this->request('GET', 'latest', [
            'base_currency' => $baseCurrency
        ]);
    }

    public function convert(float $amount, string $from, string $to): float
    {
        $rates = $this->getExchangeRates($from);
        
        if (!isset($rates['data'][$to])) {
            throw new \Exception("Exchange rate not found for {$to}");
        }

        return $amount * $rates['data'][$to];
    }

    public function getCurrencies(): array
    {
        return $this->request('GET', 'currencies');
    }
}