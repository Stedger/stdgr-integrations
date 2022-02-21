<?php

namespace Stedger\APIIntegration\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class ShipmentSaveAfter implements ObserverInterface
{
    private $shipment;
    private $api;
    private $logger;

    public function __construct(
        \Magento\Sales\Model\Order\Shipment $shipment,
        \Stedger\APIIntegration\Model\Api   $api,
        \Psr\Log\LoggerInterface            $logger
    )
    {
        $this->shipment = $shipment;
        $this->api = $api;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        try {
            $shipment = $observer->getShipment();
            $order = $shipment->getOrder();

            $orderStedgerIntegrationId = $order->getStedgerIntegrationId();

            if ($orderStedgerIntegrationId) {

                $stedgerShipment = [
                    'trackingCompany' => '',
                    'trackingNumber' => '',
                    'trackingUrl' => '',
                ];

                if (count($shipment->getAllTracks())) {
                    foreach ($shipment->getAllTracks() as $track) {
                        $stedgerShipment['trackingNumber'] = $track->getTitle();
                        $stedgerShipment['trackingNumber'] = $track->getTrackNumber();
                    }
                }

                foreach ($shipment->getAllItems() as $item) {
                    $stedgerShipment['lineItems'][] = [
                        'orderLineId' => $item->getOrderItem()->getStedgerIntegrationId(),
                        'quantity' => (int)$item->getQty(),
                    ];
                }

                $this->api->request('POST', "orders/$orderStedgerIntegrationId/fulfillments", $stedgerShipment, null, $order->getStoreId());
            }
        } catch (\Exception $e) {
            $this->logger->critical('Error "shipment save after": ' . $e->getMessage(), ['exception' => $e]);
        }
        return $this;
    }
}
