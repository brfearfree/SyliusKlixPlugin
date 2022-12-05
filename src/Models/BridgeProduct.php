<?php

namespace WhiteDigital\SyliusKlixPlugin\Models;

class BridgeProduct{
    function __construct(public string $name, public $unitPrice, public $quantity){

    }
}