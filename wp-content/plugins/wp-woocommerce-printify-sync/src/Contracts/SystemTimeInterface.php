<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Contracts;

interface SystemTimeInterface
{
    public function getCurrentUTCDateTime(): \DateTimeInterface;
    public function formatDateTime(\DateTimeInterface $dateTime, string $format = 'Y-m-d H:i:s'): string;
}