<?php

namespace RetailExpress\SkyLink\Api\Catalogue\Attributes;

use Magento\Eav\Api\Data\AttributeInterface;
use RetailExpress\SkyLink\Sdk\Catalogue\Attributes\AttributeCode as SkyLinkAttributeCode;

interface MagentoAttributeServiceInterface
{
    /**
     * Defines the Attribute used when SkyLink synchronises a Product.
     *
     * @param AttributeInterface   $magentoAttribute
     * @param SkyLinkAttributeCode $skylinkAttributeCode
     */
    public function mapMagentoAttributeForSkyLinkAttributeCode(
        AttributeInterface $magentoAttribute,
        SkyLinkAttributeCode $skylinkAttributeCode
    );
}
