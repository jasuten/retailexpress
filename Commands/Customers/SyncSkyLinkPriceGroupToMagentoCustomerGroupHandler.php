<?php

namespace RetailExpress\SkyLink\Commands\Customers;

use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use RetailExpress\SkyLink\Api\Customers\MagentoCustomerGroupRepositoryInterface;
use RetailExpress\SkyLink\Api\Customers\MagentoCustomerGroupServiceInterface;
use RetailExpress\SkyLink\Exceptions\Customers\SkyLinkPriceGroupDoesNotExistException;
use RetailExpress\SkyLink\Sdk\Customers\PriceGroups\PriceGroupKey as SkyLinkPriceGroupKey;
use RetailExpress\SkyLink\Sdk\Customers\PriceGroups\PriceGroupRepositoryFactory;
use RetailExpress\SkyLink\Api\Debugging\SkyLinkLoggerInterface;

class SyncSkyLinkPriceGroupToMagentoCustomerGroupHandler
{
    private $skyLinkPriceGroupRepositoryFactory;

    private $magentoCustomerGroupRepository;

    private $magentoCustomerGroupService;

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
        PriceGroupRepositoryFactory $skyLinkPriceGroupRepositoryFactory,
        MagentoCustomerGroupRepositoryInterface $magentoCustomerGroupRepository,
        MagentoCustomerGroupServiceInterface $magentoCustomerGroupService,
        SkyLinkLoggerInterface $logger,
        EventManagerInterface $eventManager
    ) {
        $this->skyLinkPriceGroupRepositoryFactory = $skyLinkPriceGroupRepositoryFactory;
        $this->magentoCustomerGroupRepository = $magentoCustomerGroupRepository;
        $this->magentoCustomerGroupService = $magentoCustomerGroupService;
        $this->logger = $logger;
        $this->eventManager = $eventManager;
    }

    public function handle(SyncSkyLinkPriceGroupToMagentoCustomerGroupCommand $command)
    {
        $skyLinkPriceGroupKey = SkyLinkPriceGroupKey::fromString($command->skyLinkPriceGroupKey);

        /* @var \RetailExpress\SkyLink\Sdk\Customers\PriceGroups\PriceGroupRepository $skyLinkPriceGroupRepository */
        $skyLinkPriceGroupRepository = $this->skyLinkPriceGroupRepositoryFactory->create();

        /* @var \RetailExpress\SkyLink\Sdk\Customers\PriceGroups\PriceGroup $skyLinkPriceGroup */
        $skyLinkPriceGroup = $skyLinkPriceGroupRepository->find($skyLinkPriceGroupKey);

        if (null === $skyLinkPriceGroup) {
            $e = SkyLinkPriceGroupDoesNotExistException::withSkyLinkPriceGroupKey($skyLinkPriceGroupKey);

            $this->logger->error('Syncing SkyLink Price Group to Magento Customer Group', [
                'Error' => $e->getMessage(),
                'SkyLink Price Group Type' => $skyLinkPriceGroupKey->getType(),
                'SkyLink Price Group ID' => $skyLinkPriceGroupKey->getId(),
            ]);

            throw $e;
        }

        $this->logger->info('Syncing SkyLink Price Group to Magento Customer Group', [
            'SkyLink Price Group ID' => $skyLinkPriceGroup->getKey()->getId(),
            'SkyLink Price Group Name' => $skyLinkPriceGroup->getNameWithType(),
        ]);

        /* @var \Magento\Customer\Api\Data\GroupInterface|null $magentoCustomerGroup */
        $magentoCustomerGroup = $this->magentoCustomerGroupRepository->findBySkyLinkPriceGroupKey($skyLinkPriceGroupKey);

        if (null === $magentoCustomerGroup) {
            $this->logger->debug('No Magento Customer Group exists for SkyLink Price Group, creating one.', [
                'SkyLink Price Group ID' => $skyLinkPriceGroup->getKey()->getId(),
                'SkyLink Price Group Name' => $skyLinkPriceGroup->getNameWithType(),
            ]);

            $magentoCustomerGroup = $this->magentoCustomerGroupService->createMagentoCustomerGroup($skyLinkPriceGroup);
        } else {
            $this->logger->debug('Found existing Magento Customer Group for SkyLink Price Group, updating.', [
                'SkyLink Price Group ID' => $skyLinkPriceGroup->getKey()->getId(),
                'SkyLink Price Group Name' => $skyLinkPriceGroup->getNameWithType(),
                'Magento Customer Group ID' => $magentoCustomerGroup->getId(),
                'Magento Customer Group Code' => $magentoCustomerGroup->getCode(),
            ]);

            $magentoCustomerGroup = $this->magentoCustomerGroupService
                ->updateMagentoCustomerGroup($magentoCustomerGroup, $skyLinkPriceGroup);
        }

        $this->eventManager->dispatch(
            'retail_express_skylink_skylink_price_group_was_synced_to_magento_customer_group',
            [
                'command' => $command,
                'skylink_price_group' => $skyLinkPriceGroup,
                'magento_customer_group' => $magentoCustomerGroup,
            ]
        );
    }
}
