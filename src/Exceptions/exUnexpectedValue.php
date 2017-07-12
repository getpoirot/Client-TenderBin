<?php
namespace Poirot\TenderBinClient\Exceptions;


class exUnexpectedValue
    extends \RuntimeException
{
    protected $code = 400;
}
