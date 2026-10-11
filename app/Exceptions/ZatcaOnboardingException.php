<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use \Exception;


class ZatcaOnboardingException extends Exception 
{
    private $additionalData;

    public function __construct(
        $message = "", 
        $code = 0, 
        \Throwable $previous = null, 
        $additionalData = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->additionalData = $additionalData;
    }

    public function getAdditionalData()
    {
        return $this->additionalData;
    }
}