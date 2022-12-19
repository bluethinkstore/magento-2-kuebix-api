<?php

namespace Bluethink\Kuebix\Model\Source;

class FreightClass extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
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
