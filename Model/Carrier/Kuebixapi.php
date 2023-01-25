<?php
/**
 * Copyright © Bluethinkinc@copyright All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bluethinkinc\Kuebix\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;

class Kuebixapi extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements
    \Magento\Shipping\Model\Carrier\CarrierInterface
{
    /**
     * @var string
     */
    protected $_code = 'kuebixapi';

    /**
     * @var boolean
     */
    protected $_isFixed = true;

    /**
     * @var integer
     */
    protected $_minLength = 5;

    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    protected $_rateResultFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory
     */
    protected $_rateMethodFactory;

    /**
     * @var \Bluethinkinc\Kuebix\Helper\Apidata
     */
    protected $_apiData;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $_coreSession;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param \Bluethinkinc\Kuebix\Helper\Apidata $apiData
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Session\SessionManagerInterface $coreSession
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Bluethinkinc\Kuebix\Helper\Apidata $apiData,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Session\SessionManagerInterface $coreSession,
        array $data = []
    ) {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->_apiData = $apiData;
        $this->_coreSession = $coreSession;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * Get Collect rate
     *
     * @param object $request
     * @return object $result
     */
    public function collectRates(RateRequest $request)
    {
        
        $postCode = '';
        
        $postCode = $request->getDestPostcode();

        if (!$this->getConfigFlag('active') || strlen((string)$postCode)<$this->_minLength) {
            return false;
        }
        
        $data = $this->_apiData->makeACurlRequest($request->getDestPostcode());
      
        $shippingPriceArray = [];
        if (isset($data['rates'])) {

            foreach ($data['rates'] as $shipingData) {

                $shippingPriceArray[]=[
                  'price'=>$shipingData['totalPrice'],
                  'carrierName'=>$shipingData['carrierName'],
                  'quoteNumber'=>$shipingData['quoteNumber'],

                ];

            }

            $prices = array_column($shippingPriceArray, 'price');
            $minArray = $shippingPriceArray[array_search(min($prices), $prices)];
            $shippingPrice = 0;
            $shippingPriceAmount = 0;
            $checkPrice = isset($minArray['price']) ? $minArray['price'] : '' ;
            $checkCarrierName = isset($minArray['carrierName']) ? $minArray['carrierName'] : '';
            $checkQuoteNumber = isset($minArray['quoteNumber']) ? $minArray['quoteNumber'] : '';

            if (!empty($minArray) && $checkPrice && $checkCarrierName && $checkQuoteNumber) {
                $shippingPrice = $minArray['price'];
                $shippingValueInPercentage = $this->_apiData->getPercentageValue();
            
                if ($shippingValueInPercentage!='') {
                    $shippingPriceAmount = $shippingPrice + ($shippingPrice*$shippingValueInPercentage/100);
                } else {
                    $shippingPriceAmount = $shippingPrice;
                }
            
                $carrierName = $minArray['carrierName'];
                $quoteNumber = $minArray['quoteNumber'];
            } else {
                return false;
            }
        
            $this->_coreSession->start();
            $this->_coreSession->setCarrierName($carrierName);
            $this->_coreSession->setQuoteNumber($quoteNumber);

            $result = $this->_rateResultFactory->create();

            if ($shippingPriceAmount !== false) {
                $method = $this->_rateMethodFactory->create();
                $method->setCarrier($this->_code);
                $method->setCarrierTitle($this->getConfigData('title'));
                $method->setMethod($this->_code);
                $method->setMethodTitle($this->getConfigData('name'));
                $method->setPrice($shippingPriceAmount);
                $method->setCost($shippingPriceAmount);
                $result->append($method);
            }

            return $result;
        }
    }

    /**
     * GetAllowedMethods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }
}
