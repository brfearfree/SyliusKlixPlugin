<?php

declare(strict_types=1);

namespace WhiteDigital\SyliusKlixPlugin\Action;

use ArrayObject;
use Klix\Model\Purchase;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\Notify;
use Sylius\Component\Core\Model\PaymentInterface;
use Webmozart\Assert\Assert;
use WhiteDigital\SyliusKlixPlugin\Bridge\KlixBridgeInterface;

final class NotifyAction extends ActionBase implements ActionInterface, ApiAwareInterface
{
    use GatewayAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function execute($request): void
    {
        /** @var $request Notify */
        RequestNotSupportedException::assertSupports($this, $request);
        /** @var PaymentInterface $payment */
        $payment = $request->getFirstModel();
        Assert::isInstanceOf($payment, PaymentInterface::class);

        $model = $request->getModel();

        if(!isSet($model['klixOrderId']) || $model['klixOrderId']){
            throw new HttpResponse('SUCCESS');
        }

        if ('POST' === $_SERVER['REQUEST_METHOD']) {
            $notification = $this->klixBridge->consumeNotification(file_get_contents('php://input'));

            if(($notification) && ($notification->id === $model['klixOrderId'])){
                try {
                    $result = $this->klixBridge->retrieve($notification->id);
                    if(isSet($result)
                        && ($result instanceof Purchase)
                        && isSet($result->status)
                        && in_array($result->status, KlixBridgeInterface::COMPLETED_PAYMENT_STATUSES)
                    ){
                        $model['statusKlix'] = $result->status;
                        $request->setModel($model);
                    }
                } catch (\Exception $e) {
                    throw new HttpResponse($e->getMessage());
                }
            }
        }

        throw new HttpResponse('SUCCESS');
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request): bool
    {
        return $request instanceof Notify &&
            $request->getModel() instanceof ArrayObject
        ;
    }
}
