<?php

declare(strict_types=1);

namespace WhiteDigital\SyliusKlixPlugin\Action;

use Klix\Model\Purchase;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Request\Capture;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Payum\Core\Security\TokenInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Webmozart\Assert\Assert;
use WhiteDigital\SyliusKlixPlugin\Exception\KlixException;
use WhiteDigital\SyliusKlixPlugin\Models\BridgeCustomer;
use WhiteDigital\SyliusKlixPlugin\Models\BridgeOrder;
use WhiteDigital\SyliusKlixPlugin\Models\BridgeProduct;

final class CaptureAction extends ActionBase implements ActionInterface, ApiAwareInterface, GenericTokenFactoryAwareInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    /** @var GenericTokenFactoryInterface */
    private $tokenFactory;

    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = $request->getModel();
        /** @var OrderInterface $orderData */
        $order = $request->getFirstModel()->getOrder();

        /** @var TokenInterface $token */
        $token = $request->getToken();
        $klixData = $this->prepareOrderData($token, $order);

        /** @var Purchase|null $result */
        $result = $this->klixBridge->create($klixData);

        if (null !== $model['orderId']) {
            /** @var Purchase $purchase */
            $purchase = $this->klixBridge->retrieve((string) $model['orderId']);

            if(($purchase instanceof Purchase::class) && isSet($purchase->status)){
                $model['statusKlix'] = $purchase->status;
                $request->setModel($model);
            }
        }

        if (($result instanceof Purchase::class) && isSet($result->id)) {
            $model['orderId'] = $result->id;
            $model['statusKlix'] = $result->status;

            $request->setModel($model);

            throw new HttpRedirect($result->checkout_url);
        }

        throw KlixException::newInstance($result);
    }

    public function setGenericTokenFactory(GenericTokenFactoryInterface $genericTokenFactory = null): void
    {
        $this->tokenFactory = $genericTokenFactory;
    }

    public function supports($request): bool
    {
        return
            $request instanceof Capture
            && $request->getModel() instanceof ArrayObject;
    }

    private function prepareOrderData(TokenInterface $token, OrderInterface $order): BridgeOrder
    {
        $bridgeOrder = new BridgeOrder();

        $notifyToken = $this->tokenFactory->createNotifyToken($token->getGatewayName(), $token->getDetails());

        $bridgeOrder->success_redirect = $token->getTargetUrl();
        $bridgeOrder->failure_redirect = $token->getTargetUrl();
        $bridgeOrder->failure_redirect = $token->getTargetUrl();

        $bridgeOrder->customerIp = $order->getCustomerIp();
        $bridgeOrder->description = $order->getNumber();
        $bridgeOrder->currencyCode = $order->getCurrencyCode();
        $bridgeOrder->totalAmount = $order->getTotal();

        /** @var CustomerInterface $customer */
        $customer = $order->getCustomer();

        Assert::isInstanceOf(
            $customer,
            CustomerInterface::class,
            sprintf(
                'Make sure the first model is the %s instance.',
                CustomerInterface::class
            )
        );

        $bridgeOrder->customer = new BridgeCustomer();
        $bridgeOrder->customer->email = (string) $customer->getEmail();
        $bridgeOrder->customer->firstName = (string) $customer->getFirstName();
        $bridgeOrder->customer->lastName = (string) $customer->getLastName();
        $bridgeOrder->customer->language = $this->getFallbackLocaleCode($order->getLocaleCode());

        $bridgeOrder->products = $this->getOrderItems($order);

        return $bridgeOrder;
    }

    private function getOrderItems(OrderInterface $order): array
    {
        $itemsData = [];

        if ($items = $order->getItems()) {
            /** @var OrderItemInterface $item */
            foreach ($items as $key => $item) {
                $itemsData[] = new BridgeProduct(
                    $item->getProductName(),
                    $item->getUnitPrice(),
                    $item->getQuantity()
                );
            }
        }

        return $itemsData;
    }

    private function getFallbackLocaleCode(string $localeCode): string
    {
        return explode('_', $localeCode)[0];
    }
}
