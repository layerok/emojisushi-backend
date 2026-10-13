<?php

namespace Layerok\PosterPos\Classes\Couriers;

/**
 * A status change that did not happen, carrying the HTTP status the courier app
 * uses to decide whether to retry (503) or give up and say why (403, 409, 422).
 */
class StatusChangeException extends \RuntimeException
{
    public function __construct(string $message, private int $httpStatus)
    {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }
}
