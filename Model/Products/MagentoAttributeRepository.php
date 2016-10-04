<?php

namespace RetailExpress\SkyLink\Model\Products;

use Magento\Eav\Api\AttributeRepositoryInterface as BaseMagentoAttributeRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use RetailExpress\SkyLink\Api\Products\MagentoAttributeRepositoryInterface;
use RetailExpress\SkyLink\Catalogue\Attributes\AttributeCode as SkyLinkAttributeCode;

class MagentoAttributeRepository implements MagentoAttributeRepositoryInterface
{
    use MagentoAttribute;

    /**
     * The base Magento Attribute Repository, used for fetching attributes based
     * on their attribute code stored by a mapping.
     *
     * @var BaseMagentoAttributeRepositoryInterface
     */
    private $baseMagentoAttributeRepository;

    /**
     * Create a new Magento Attribute Repository.
     *
     * @param ResourceConnection                      $resourceConnection
     * @param BaseMagentoAttributeRepositoryInterface $baseMagentoAttributeRepository
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        BaseMagentoAttributeRepositoryInterface $baseMagentoAttributeRepository
    ) {
        $this->connection = $resourceConnection->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $this->baseMagentoAttributeRepository = $baseMagentoAttributeRepository;
    }

    /**
     * Get the Attribute used for the given SkyLink Attribute Code. If there is no
     * mapping defined, "null" is returend.
     *
     * @param SkyLinkAttributeCode $skylinkAttributeCode
     *
     * @return AttributeInterface|null
     */
    public function getMagentoAttributeForSkyLinkAttributeCode(SkyLinkAttributeCode $skylinkAttributeCode)
    {
        $magentoAttributeCode = $this->connection->fetchOne(
            $this->connection
                ->select()
                ->from($this->getAttributesTable(), 'magento_attribute_code')
                ->where('skylink_attribute_code = ?', $skylinkAttributeCode->getValue())
        );

        if (false === $magentoAttributeCode) {
            return null;
        }

        return $this->baseMagentoAttributeRepository->get('catalog_product', $magentoAttributeCode);
    }
}