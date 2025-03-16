<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Data;

class CountryData
{
    public static function extendCountries(array $countries): array
    {
        foreach ($countries as $code => &$name) {
            $currency = self::getCountryCurrency($code);
            if ($currency) {
                $name .= " ({$currency})";
            }
        }
        return $countries;
    }

    private static function getCountryCurrency(string $code): ?string
    {
        $currencies = [
            'US' => 'USD',
            'GB' => 'GBP',
            'EU' => 'EUR', // For European countries
            'CA' => 'CAD',
            'AU' => 'AUD',
            // Add more as needed
        ];

        return $currencies[$code] ?? null;
    }
}