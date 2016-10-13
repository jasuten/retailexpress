<?php

namespace RetailExpress\SkyLink\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface as DbAdapterInterface;
use Magento\Framework\DB\Ddl\Table as DdlTable;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class InstallSchema implements InstallSchemaInterface
{
    use InstallEdsSchema;
    use InstallCatalogueSchema;

    /**
     * {@inheritdoc}
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $this->installEdsSchema($setup, $context);
        $this->InstallCatalogueSchema($setup, $context);
    }
}
