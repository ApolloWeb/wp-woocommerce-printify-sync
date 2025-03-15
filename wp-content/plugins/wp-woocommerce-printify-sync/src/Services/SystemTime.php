<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Contracts\SystemTimeInterface;

class SystemTime implements SystemTimeInterface
{
    private string $currentTime;

    public function __construct(string $currentTime)
    {
        $this->currentTime = $currentTime;
    }

    public function getCurrentUTCDateTime(): \DateTimeInterface
    {
        return new \DateTime($this->currentTime, new \DateTimeZone('UTC'));
    }

    public function formatDateTime(\DateTimeInterface $dateTime, string $format = 'Y-m-d H:i:s'): string
    {
        return $dateTime->format($format);
    }
}