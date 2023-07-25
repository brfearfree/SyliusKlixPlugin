<?php

declare(strict_types=1);

namespace WhiteDigital\SyliusKlixPlugin\Bridge;

use WhiteDigital\SyliusKlixPlugin\Models\BridgeOrder;

interface KlixBridgeInterface
{
    public const CREATED_PAYMENT_STATUSES = ['created'];
    public const PENDING_PAYMENT_STATUSES = ['pending_execute', 'pending_release', 'pending_capture', 'viewed', 'sent', 'overdue'];
    public const COMPLETED_PAYMENT_STATUSES = ['paid', 'cleared', 'settled'];
    public const CANCELED_PAYMENT_STATUSES = ['cancelled', 'expired', 'released'];
    public const REJECTED_PAYMENT_STATUSES = ['error', 'blocked'];
    public const OTHER_PAYMENT_STATUSES = ['preauthorized', 'pending_charge', 'chargeback', 'pending_refund', 'refunded'];

    public const SECURE_ENVIRONMENT = 'Secure';
    public const SANDBOX_ENVIRONMENT = 'Sandbox';

    public const NEW_PAYMENT_STATUS = 'created';
    public const ERROR_PAYMENT_STATUS = 'error';

    public function setAuthorizationData(
        string $brand_id,
        string $api_key,
        string $endpoint
    ): void;

    public function setCustomTargetUrl(string $url): void;

    public function getCustomTargetUrl() :string;

    public function create(BridgeOrder $order);

    public function retrieve(string $orderId);

    public function consumeNotification($data);

    public function setCustomNotifyUrl(string $url): void;

    public function getCustomNotifyUrl() :string;


}
