<?php

namespace App\Services\Zatca\Tags;

use App\Services\Zatca\InvoiceCreator\Tag;

class Seller extends Tag
{
    public function __construct($value)
    {
        parent::__construct(1, $value);
    }
}
