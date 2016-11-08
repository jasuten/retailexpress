<?php

namespace RetailExpress\SkyLink\Api\Catalogue\Products;

use Magento\Catalog\Api\Data\ProductInterface;
use RetailExpress\SkyLink\Sdk\Catalogue\Products\MatrixPolicy as SkyLinkMatrixPolicy;

interface MagentoConfigurableProductLinkManagementInterface
{
    /**
     * Gets the ID of the parent product for the given child product.
     *
     * @return int|null
     */
    public function getParentProductId(ProductInterface $childProduct);

    /**
     * Syncs the given children to the configurable product provided.
     *
     * @param SkyLinkMatrixPolicy $skyLinkMatrixPolicy
     * @param ProductInterface    $parentProduct
     * @param ProductInterface[]  $childrenProducts
     */
    public function syncChildren(
        SkyLinkMatrixPolicy $skyLinkMatrixPolicy,
        ProductInterface $parentProduct,
        array $childrenProducts
    );
}
