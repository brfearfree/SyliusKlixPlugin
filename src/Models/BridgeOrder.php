<?php

namespace WhiteDigital\SyliusKlixPlugin\Models;

class BridgeOrder{
    public BridgeCustomer $customer;
    public array $products;
    public string $success_redirect;
    public string $failure_redirect;
    public string $cancel_redirect;
    public string $success_callback;
    public ?string $customerIp;
    public ?string $description;
    public ?string $currencyCode;
    public int $totalAmount;

}