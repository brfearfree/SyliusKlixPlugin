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
use WhiteDigital\SyliusKlixPlugin\Bridge\KlixBridgeInterface;
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

        $redirect_to_payment_portal = false;

        if (null !== $model['klixOrderId']) {
            // Retrive transaction data
            try{
                /** @var Purchase $result */
                $result = $this->klixBridge->retrieve((string) $model['klixOrderId']);
            }
            catch(\Exception $exception){
                // Transaction does not exists
                $result = $exception;
            }

            if(isSet($result) && ($result instanceof Purchase) && isSet($result->status)){
                if(in_array($result->status, KlixBridgeInterface::CREATED_PAYMENT_STATUSES)) {
                    if($model['klixResult']->checkout_url ?? false){
                        throw new HttpRedirect($result->checkout_url);
                    }
                    else{
                        $model['statusKlix'] = KlixBridgeInterface::ERROR_PAYMENT_STATUS;
                    }
                }
                else{
                    $model['statusKlix'] = $result->status;
                }
            }
            else{
                $model['statusKlix'] = KlixBridgeInterface::ERROR_PAYMENT_STATUS;
            }

            $request->setModel($model);
            return;
        }

        // We need to create new transaction if got so far
        /** @var TokenInterface $token */
        $token = $request->getToken();
        $notifyToken = $this->tokenFactory->createNotifyToken($token->getGatewayName(), $token->getDetails());

        $locale = $order->getLocaleCode();
        if(!$locale){
            $locale = 'lv_LV';
        }

        if($configured_target_url = $this->klixBridge->getCustomTargetUrl()){
            $hash = $token->getHash();
            if(stripos('{token}', $configured_target_url) !== false){
                $final_url = str_replace('{token}', $hash, $configured_target_url);
            }
            else{
                $final_url = $configured_target_url . '?payum_token=' . $hash;
            }

            if(stripos('{locale}', $configured_target_url) !== false){
                $final_url = str_replace('{locale}', $locale, $configured_target_url);
            }
            else{
                $final_url .= '&locale=' . $locale;
            }

            $token->setTargetUrl($final_url);
        }

        if($configured_notify_url = $this->klixBridge->getCustomNotifyUrl()){
            $hash = $notifyToken->getHash();

            if(stripos('{token}', $configured_notify_url) !== false){
                $final_url = str_replace('{token}', $hash, $configured_notify_url);
            }
            else{
                $final_url = $configured_notify_url . '?payum_token=' . $hash;
            }

            if(stripos('{locale}', $configured_target_url) !== false){
                $final_url = str_replace('{locale}', $locale, $configured_target_url);
            }
            else{
                $final_url .= '&locale=' . $locale;
            }

            $notifyToken->setTargetUrl($final_url);
        }

        $klixData = $this->prepareOrderData($token, $order, $notifyToken);

        try {
            /** @var Purchase|null $result */
            $result = $this->klixBridge->create($klixData);
        }
        catch(\Exception $exception){
            $result = $exception;
        }

        if (isSet($result) && ($result instanceof Purchase) && isSet($result->id) && ($model['statusKlix']===null)) {
            $model['klixOrderId'] = $result->id;
            $model['statusKlix'] = $result->status;

            $model['klixResult'] = $result;

            $request->setModel($model);

            throw new HttpRedirect($result->checkout_url);
        }
        
        if(!isSet($result)){
            $result = new \StdClass();
            $result->code = 'ERROR';
            $result->message = 'Failed to initialize KLIX transaction!';
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

    private function prepareOrderData(TokenInterface $token, OrderInterface $order, TokenInterface $notifyToken): BridgeOrder
    {
        $bridgeOrder = new BridgeOrder();

        $targetUrl = $token->getTargetUrl();

        $bridgeOrder->success_redirect = $targetUrl;
        $bridgeOrder->failure_redirect = $targetUrl;
        $bridgeOrder->cancel_redirect = $targetUrl;

        $bridgeOrder->success_callback = $notifyToken->getTargetUrl();

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
