<?php

namespace App\Services\Zatca\Tags;

use App\Services\Zatca\InvoiceCreator\Tag;

class CertificateSignature extends Tag
{
    public function __construct($value)
    {
        parent::__construct(9, $value);
    }
}
