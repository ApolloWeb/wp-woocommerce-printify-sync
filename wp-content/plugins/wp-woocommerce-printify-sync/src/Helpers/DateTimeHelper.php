<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

class DateTimeHelper
{
    private string $timezone = 'UTC';

    public function getCurrentTimestamp(): string
    {
        return (new \DateTime('now', new \DateTimeZone($this->timezone)))
            ->format('Y-m-d H:i:s');
    }

    public function formatDate(string $date, string $format = 'Y-m-d H:i:s'): string
    {
        return (new \DateTime($date, new \DateTimeZone($this->timezone)))
            ->format($format);
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }
}