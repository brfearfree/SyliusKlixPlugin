<?php

declare(strict_types=1);

namespace WhiteDigital\SyliusKlixPlugin\Action;

use ArrayAccess;
use WhiteDigital\SyliusKlixPlugin\Bridge\KlixBridgeInterface;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetStatusInterface;

final class StatusAction extends ActionBase implements ActionInterface
{

    /**
     * {@inheritdoc}
     */
    public function execute($request): void
    {
        /** @var GetStatusInterface $request */
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());
        $status = $model['statusKlix'] ?? null;
        $orderId = $model['orderId'] ?? null;

        if ((null === $status || in_array($status,KlixBridgeInterface::CREATED_PAYMENT_STATUSES)) && null !== $orderId) {
            $request->markNew();

            return;
        }

        if (in_array($status, KlixBridgeInterface::PENDING_PAYMENT_STATUSES)) {
            $request->markPending();

            return;
        }

        if (in_array($status, KlixBridgeInterface::CANCELED_PAYMENT_STATUSES)) {
            $request->markCanceled();

            return;
        }

        if (in_array($status, KlixBridgeInterface::REJECTED_PAYMENT_STATUSES)) {
            $request->markFailed();

            return;
        }

        if (in_array($status, KlixBridgeInterface::COMPLETED_PAYMENT_STATUSES)) {
            $request->markCaptured();

            return;
        }

        $request->markUnknown();
    }

    public function supports($request): bool
    {
        return $request instanceof GetStatusInterface &&
            $request->getModel() instanceof ArrayAccess
        ;
    }
}
