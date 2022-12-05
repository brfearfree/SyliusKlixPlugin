<?php

declare(strict_types=1);

namespace WhiteDigital\SyliusKlixPlugin\Exception;

use Payum\Core\Exception\Http\HttpException;

final class KlixException extends HttpException
{
    public const LABEL = 'KlixException';

    public static function newInstance($status) :KlixException
    {
        $parts = [self::LABEL];

        if (property_exists($status, 'code')) {
            $part = '[code]';
            if(property_exists($status, 'message')){
                $part .= ' ' . $status->message;
            }
            $parts[] = $part;
        }

        $message = implode(\PHP_EOL, $parts);

        return new KlixException($message);
    }
}
