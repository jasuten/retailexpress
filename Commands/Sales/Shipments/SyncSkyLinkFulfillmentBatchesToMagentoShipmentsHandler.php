<?php

namespace RetailExpress\SkyLink\Commands\Sales\Shipments;

use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use RetailExpress\SkyLink\Api\ConfigInterface;
use RetailExpress\SkyLink\Api\Debugging\SkyLinkLoggerInterface;
use RetailExpress\SkyLink\Api\Sales\Orders\MagentoOrderRepositoryInterface;
use RetailExpress\SkyLink\Api\Sales\Shipments\MagentoShipmentServiceInterface;
use RetailExpress\SkyLink\Api\Sales\Shipments\MagentoShipmentRepositoryInterface;
use RetailExpress\SkyLink\Exceptions\Sales\Orders\SkyLinkOrderDoesNotExistException;
use RetailExpress\SkyLink\Sdk\Sales\Fulfillments\Batch as SkyLinkFulfillmentBatch;
use RetailExpress\SkyLink\Sdk\Sales\Orders\OrderId as SkyLinkOrderId;
use RetailExpress\SkyLink\Sdk\Sales\Orders\OrderRepositoryFactory as SkyLinkOrderRepositoryFactory;

class SyncSkyLinkFulfillmentBatchesToMagentoShipmentsHandler
{
    private $magentoOrderRepository;

    private $skyLinkOrderRepositoryFactory;

    private $magentoShipmentRepository;

    private $magentoShipmentService;

    /**
     * Logger instance.
     *
     * @var SkyLinkLoggerInterface
     */
    private $logger;

    /**
     * Event Manager instance.
     *
     * @var EventManagerInterface
     */
    private $eventManager;

    public function __construct(
        MagentoOrderRepositoryInterface $magentoOrderRepository,
        SkyLinkOrderRepositoryFactory $skyLinkOrderRepositoryFactory,
        MagentoShipmentRepositoryInterface $magentoShipmentRepository,
        MagentoShipmentServiceInterface $magentoShipmentService,
        SkyLinkLoggerInterface $logger,
        EventManagerInterface $eventManager
    ) {
        $this->magentoOrderRepository = $magentoOrderRepository;
        $this->skyLinkOrderRepositoryFactory = $skyLinkOrderRepositoryFactory;
        $this->magentoShipmentRepository = $magentoShipmentRepository;
        $this->magentoShipmentService = $magentoShipmentService;
        $this->logger = $logger;
        $this->eventManager = $eventManager;
    }

    public function handle(SyncSkyLinkFulfillmentBatchesToMagentoShipmentsCommand $command)
    {
        /* @var \SkyLinkOrderId $skyLinkOrderId */
        $skyLinkOrderId = new SkyLinkOrderId($command->skyLinkOrderId);

        /* @var \Magento\Sales\Api\Data\OrderInterface $magentoOrder */
        $magentoOrder = $this->magentoOrderRepository->findBySkyLinkOrderId($skyLinkOrderId);

        $this->logger->info('Syncing all SkyLink Fulfillments to Magento Shipments for SkyLink Order.', [
            'Magento Order ID' => $magentoOrder->getIncrementId(),
            'SkyLink Order ID' => $skyLinkOrderId,
        ]);

        /* @var \RetailExpress\SkyLink\Sdk\Sales\Orders\OrderRepository $skyLinkOrderRepository */
        $skyLinkOrderRepository = $this->skyLinkOrderRepositoryFactory->create();

        /* @var \RetailExpress\SkyLink\Sdk\Sales\Orders\Order $skyLinkOrder */
        $skyLinkOrder = $skyLinkOrderRepository->get($skyLinkOrderId);

        if (null === $skyLinkOrder) {
            $e = SkyLinkOrderDoesNotExistException::withSkyLinkOrderId($skyLinkOrderId);

            $this->logger->error('Cannot find SkyLink Order to sync Fulfillment Batches for. Has it been cancelled in Retail Express and hidden from the API?', [
                'Error' => $e->getMessage(),
                'Magento Order ID' => $magentoOrder->getIncrementId(),
                'SkyLink Order ID' => $skyLinkOrderId,
            ]);

            throw $e;
        }

        if (false === $skyLinkOrder->hasFulfillmentBatches()) {
            $this->logger->debug('There are no Fulfillments for SkyLink Order', [
                'Magento Order ID' => $magentoOrder->getIncrementId(),
                'SkyLink Order ID' => $skyLinkOrderId,
            ]);
        }

        // Iterate over the fulfillment batches for the SkyLink Order and try find our
        // own corresponding shipments. If we don't find one, we'll create it instead.
        /* @var \Magento\Sales\Api\Data\ShipmentInterface[] $magentoShipments */
        $magentoShipments = array_map(
            function (SkyLinkFulfillmentBatch $skyLinkFulfillmentBatch) use ($skyLinkOrder, $magentoOrder) {

                /* @var \Magento\Sales\Api\Data\ShipmentInterface|null $magentoShipment */
                $magentoShipment = $this->magentoShipmentRepository->findBySkyLinkFulfillmentBatchId(
                    $skyLinkFulfillmentBatch->getId()
                );

                if (null !== $magentoShipment) {

                    $this->logger->debug('Magento Shipment already exists for SkyLink Fulfillment Batch, skipping.', [
                        'Magento Order ID' => $magentoOrder->getIncrementId(),
                        'SkyLink Order ID' => $skyLinkOrder->getId(),
                        'Magento Shipment ID' => $magentoShipment->getId(),
                        'SkyLink Fulfillment Batch' => [
                            'ID' => $skyLinkFulfillmentBatch->getId(),
                            'Number of Fulfillments' => count($skyLinkFulfillmentBatch->getFulfillments()),
                            'Fulfilled At' => $skyLinkFulfillmentBatch->getFulfilledAt(),
                        ],
                    ]);

                    return;
                }

                $this->logger->debug('Creating new Magento Shipment for batch of SkyLink Fulfillments.', [
                    'Magento Order ID' => $magentoOrder->getIncrementId(),
                    'SkyLink Order ID' => $skyLinkOrder->getId(),
                    'SkyLink Fulfillment Batch' => [
                        'ID' => $skyLinkFulfillmentBatch->getId(),
                        'Number of Fulfillments' => count($skyLinkFulfillmentBatch->getFulfillments()),
                        'Fulfilled At' => $skyLinkFulfillmentBatch->getFulfilledAt(),
                    ],
                ]);

                /* @var \Magento\Sales\Api\Data\ShipmentInterface $magentoShipment */
                $magentoShipment = $this
                    ->magentoShipmentService
                    ->createMagentoShipmentFromSkyLinkFulfillmentBatch($skyLinkOrder, $skyLinkFulfillmentBatch);

                $this->logger->debug('Creating new Magento Shipment for batch of SkyLink Fulfillments.', [
                    'Magento Order ID' => $magentoOrder->getIncrementId(),
                    'SkyLink Order ID' => $skyLinkOrder->getId(),
                    'Magento Shipment ID' => $magentoShipment->getId(),
                    'SkyLink Fulfillment Batch' => [
                        'ID' => $skyLinkFulfillmentBatch->getId(),
                        'Number of Fulfillments' => count($skyLinkFulfillmentBatch->getFulfillments()),
                        'Fulfilled At' => $skyLinkFulfillmentBatch->getFulfilledAt(),
                    ],
                ]);

                return $magentoShipment;
            },
            $skyLinkOrder->getFulfillmentBatches()
        );

        $this->eventManager->dispatch(
            'retail_express_skylink_skylink_fulfillment_batches_were_synced_to_magento_shipments',
            [
                'command' => $command,
                'skylink_order' => $skyLinkOrder,
                'magento_order' => $magentoOrder,
                'magento_shipments' => $magentoShipments,
            ]
        );
    }
}
