<?php
/**
 * Copyright © Bluethink@copyright All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Bluethink\Kuebix\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Captcha\Observer\CaptchaStringResolver;

class Ordersave implements ObserverInterface
{

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $_coreSession;
    /**
     * @var string
     */
    protected $_code = 'kuebixapi_kuebixapi';
    
    /**
     * Constructor
     *
     * @param \Magento\Framework\Session\SessionManagerInterface $coreSession
     */

    public function __construct(
        \Magento\Framework\Session\SessionManagerInterface $coreSession
    ) {
        $this->_coreSession = $coreSession;
    }

    /**
     * To save the quote number and carrier name in sales order table
     *
     * @param Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $this->_coreSession->start();
        $order = $observer->getEvent()->getOrder();
        if ($observer->getEvent()->getOrder()->getShippingMethod() == $this->_code) {
            $order->setQuoteNumber($this->_coreSession->getQuoteNumber());
            $order->setCarrierName($this->_coreSession->getCarrierName());
            $order->save();
        }
        
        $this->_coreSession->unsQuoteNumber();
        $this->_coreSession->unsCarrierName();
    }
}
