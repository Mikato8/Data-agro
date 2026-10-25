<?php

declare(strict_types=1);

namespace App\Core;

final class DatabaseException extends \RuntimeException
{
    public function __construct(string $message, private string $sqlState = '')
    {
        parent::__construct($message);
    }

    public function sqlState(): string
    {
        return $this->sqlState;
    }
}
