<?php
/**
 * Copyright © Bluethinkinc@copyright All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bluethinkinc\Kuebix\Setup\Patch\Data;

use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Bluethinkinc\Kuebix\Model\Source\FreightClass;

class Freight implements \Magento\Framework\Setup\Patch\DataPatchInterface
{
   /**
    * @var \Magento\Framework\Setup\ModuleDataSetupInterface
    */
    private $setup;

   /**
    * @var \Magento\Eav\Setup\EavSetupFactory
    */
    private $eavSetup;

   /**
    * Constructor
    *
    * @param \Magento\Framework\Setup\ModuleDataSetupInterface $setup
    * @param \Magento\Eav\Setup\EavSetupFactory $eavSetup
    */

    public function __construct(
        \Magento\Framework\Setup\ModuleDataSetupInterface $setup,
        \Magento\Eav\Setup\EavSetupFactory $eavSetup
    ) {
        $this->setup    = $setup;
        $this->eavSetup = $eavSetup;
    }

   /**
    * Create Fright class attribute
    */
    public function apply()
    {
        $eavSetup  = $this->eavSetup->create(['setup' => $this->setup]);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'freight_class',
            [
               'group' => 'Product Details',
               'type' => 'int',
               'sort_order' => 50,
               'label' => 'Freight Class',
               'input' => 'select',
               'source' => FreightClass::class,
               'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
               'visible' => true,
               'required' => false,
               'user_defined' => false,
               'searchable' => false,
               'filterable' => false,
               'comparable' => false,
               'visible_on_front' => false,
               'used_in_product_listing' => true,
               'unique' => false,
               'apply_to'=>'simple,configurable,bundle,grouped'
            ]
        );
    }

   /**
    * Get Dependencies
    */
    public static function getDependencies()
    {
        return [];
    }

   /**
    * Get Aliases
    */
    public function getAliases()
    {
        return [];
    }
}
