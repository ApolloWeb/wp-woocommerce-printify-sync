<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Exceptions;

class BaseException extends \Exception
{
    protected string $currentTime;
    protected string $currentUser;

    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null)
    {
        $this->currentTime = '2025-03-15 18:09:19';
        $this->currentUser = 'ApolloWeb';
        
        parent::__construct($message, $code, $previous);
    }

    public function logError(): void
    {
        error_log(sprintf(
            '[%s] %s - User: %s - Error: %s',
            $this->currentTime,
            get_class($this),
            $this->currentUser,
            $this->getMessage()
        ));
    }
}