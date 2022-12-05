<?php

declare(strict_types=1);

namespace WhiteDigital\SyliusKlixPlugin;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

final class KlixGatewayFactory extends GatewayFactory
{
    protected function populateConfig(ArrayObject $config): void
    {
        $config->defaults(
            [
                'payum.factory_name' => 'klix',
                'payum.factory_title' => 'Klix',
            ]
        );

        if (false === (bool) $config['payum.api']) {
            $config['payum.default_options'] = [
                'brand_id' => '',
                'api_key' => '',
                'endpoint' => '',
            ];

            $config->defaults($config['payum.default_options']);

            $config['payum.required_options'] = ['brand_id', 'api_key', 'endpoint'];

            $config['payum.api'] = static function (ArrayObject $config): array {
                $config->validateNotEmpty($config['payum.required_options']);

                return [
                    'brand_id' => $config['brand_id'],
                    'api_key' => $config['api_key'],
                    'endpoint' => $config['endpoint'],
                ];
            };
        }
    }
}
