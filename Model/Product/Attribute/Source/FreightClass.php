<?php
declare(strict_types=1);

namespace Bluethinkinc\Kuebix\Model\Product\Attribute\Source;

class FreightClass extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{

    /**
     * getAllOptions
     *
     * @return array
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['label' => __('--Select--'), 'value' => ''],
                ['label' => __('Chair'), 'value' => 250],
                ['label' => __('Table'), 'value' => 300]
            ];
        }
        return $this->_options;
    }
}

