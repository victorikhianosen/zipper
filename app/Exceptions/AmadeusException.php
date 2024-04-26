<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class AmadeusException extends Exception
{
    public function __construct(string $message, int $code = 400, ?Throwable $previous = null) //@phpstan-ignore-line
    {
        $responseTitle = $this->getMessageByCode($code);
        parent::__construct($message ?: $responseTitle);
    }

    protected function getMessageByCode(int|string $code): string
    {
        return match (strval($code)) {
            '4926' => 'Invalid data received',
            '141' => 'System error has occured',
            default => 'Something isn\'t just right',
        };
    }
}
