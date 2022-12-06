<?php

namespace WhiteDigital\SyliusKlixPlugin\Action;

use Payum\Core\Exception\UnsupportedApiException;
use Psr\Log\LoggerInterface;
use WhiteDigital\SyliusKlixPlugin\Bridge\KlixBridgeInterface;

class ActionBase{

    /** @param KlixBridgeInterface $klixBridge */
    public function __construct(protected KlixBridgeInterface $klixBridge)
    {
    }

    /**
     * @throws UnsupportedApiException if the given Api is not supported.
     */
    public function setApi($api): void
    {
        if (false === is_array($api)) {
            throw new UnsupportedApiException('Not supported. Expected to be set as array.');
        }

        $this->klixBridge->setAuthorizationData(
            $api['brand_id'],
            $api['api_key'],
            $api['endpoint']
        );
    }
}