<?php

namespace App\Exceptions;

use Exception;

class ApiException extends Exception
{
    public function __construct($errorMsg, $errorCode = 0)
    {
        parent::__construct();
        $this->message = $errorMsg;
        $this->code = $errorCode;
    }
}
