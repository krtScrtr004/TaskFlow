<?php

namespace App\Exception;

use App\Abstract\CustomException;

class DatabaseException extends CustomException
{    
    public function __construct(string $message = 'A database error occurred.')
    {
        parent::__construct($message, 5000);
    }
}