<?php

namespace RetailExpress\SkyLink\Api\Sales\Orders;

interface ConfigInterface
{
    /**
     * Returns if there is a Guest Customer ID configured.
     *
     * @return bool
     */
    public function hasGuestCustomerId();

    /**
     * Returns the Guest Customer ID.
     *
     * @return \RetailExpress\SkyLink\Sdk\Customers\CustomerId|null
     *
     * @throws \RetailExpress\SkyLink\Exceptions\Sales\Orders\NoGuestCustomerIdConfiguredException
     */
    public function getGuestCustomerId();

    /**
     * Returns the default Item Fulfillment Method.
     *
     * @return \RetailExpress\SkyLink\Sdk\Sales\Orders\ItemFulfillmentMethod
     */
    public function getItemFulfillmentMethod();
}
