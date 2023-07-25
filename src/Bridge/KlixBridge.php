<?php

declare(strict_types=1);

namespace WhiteDigital\SyliusKlixPlugin\Bridge;

use Klix\KlixApi;
use Klix\Model\ClientDetails;
use Klix\Model\Product;
use Klix\Model\Purchase;
use Klix\Model\PurchaseDetails;
use WhiteDigital\SyliusKlixPlugin\Models\BridgeOrder;
use WhiteDigital\SyliusKlixPlugin\Models\BridgeProduct;

final class KlixBridge implements KlixBridgeInterface
{
    /*** @var string|null */
    private $cacheDir;

    private KlixApi $klix;
    private $brand_id;

    private $customTargetUrl;
    private $customNotifyUrl;

    public function __construct(string $cacheDir = null)
    {
        $this->cacheDir = $cacheDir;
    }

    public function setAuthorizationData(
        string $brand_id,
        string $api_key,
        string $endpoint
    ): void {
        $this->klix = new KlixApi($brand_id, $api_key, $endpoint);
        $this->brand_id = $brand_id;
    }

    public function setCustomTargetUrl(string $url): void
    {
        $this->customTargetUrl = $url;
    }

    public function getCustomTargetUrl() :string
    {
        return $this->customTargetUrl ?? '';
    }
    public function create(BridgeOrder $order) :Purchase
    {
        $purchase = new Purchase();

        $purchase->brand_id = $this->brand_id;

        $client = new ClientDetails();
        $client->email = $order->customer->email;
        $purchase->client = $client;

        $purchase->cancel_redirect = $order->cancel_redirect;
        $purchase->success_redirect = $order->success_redirect;
        $purchase->failure_redirect = $order->failure_redirect;

        $purchase->success_callback = $order->success_callback;

        $details = new PurchaseDetails();
        $details->products = [];
        /** @var BridgeProduct $bridgeProduct */
        foreach($order->products as $bridgeProduct){
            $product = new Product();
            for($i=0; $i<$bridgeProduct->quantity; $i++){
                $product->name = $bridgeProduct->name;
                $product->price = $bridgeProduct->unitPrice;
                $details->products[] = $product;
            }
        }
        $purchase->purchase = $details;

        return $this->klix->createPurchase($purchase);
    }

    public function retrieve(string $orderId)
    {
        return $this->klix->getPurchase($orderId);
    }

    public function consumeNotification($data)
    {
        $data = trim($data);
        $json = json_decode($data);

        return ($json && isset($json->id) && $json->id) ? $json : false;
    }

    public function setCustomNotifyUrl(string $url): void
    {
        $this->customNotifyUrl = $url;
    }

    public function getCustomNotifyUrl() :string
    {
        return $this->customNotifyUrl ?? '';
    }

}
