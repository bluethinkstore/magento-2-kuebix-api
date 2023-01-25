<?php
/**
 * Copyright © Bluethinkinc@copyright All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bluethinkinc\Kuebix\Helper;
 
use Magento\Framework\App\Helper\AbstractHelper;
 
class Apidata extends AbstractHelper
{
 
    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $_curl;
    
    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $_cart;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_product;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\Catalog\Model\ProductFactory $product
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
 
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Catalog\Model\ProductFactory $product,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        parent::__construct($context);
        $this->_curl = $curl;
        $this->_scopeConfig = $scopeConfig;
        $this->_cart = $cart;
        $this->_product = $product;
        $this->_messageManager = $messageManager;
    }

    /**
     * Get response from kuebix api
     *
     * @param (int) $pinCode
     * @return Array
     */
    public function makeACurlRequest($pinCode)
    {
        $lineItems=$this->getLineItems();
        $postData=$this->postDataValidate();
        $authorization=base64_encode($postData['userName'].':'.$postData['password']);
        $requestArray=[
        "origin"=>[
        "companyName"=> "",
        "country"=> "",
        "stateProvince"=> "",
        "city"=> "",
        "streetAddress" =>"",
        "postalCode"=> $postData['OriginPinCode']
        ],
        "destination"=>[
        "companyName"=> "",
        "country"=> "",
        "stateProvince"=> "",
        "city"=> "",
        "streetAddress" =>"",
        "postalCode"=> $pinCode
        ],
        "billTo"=>[
        "companyName"=> "",
        "country"=> "",
        "stateProvince"=> "",
        "city"=> "",
        "streetAddress" =>"",
        "postalCode"=> $pinCode
        ],
        "lineItems"=>$lineItems,
        "client"=> [
        "id"=> $postData['clientId']
        ],
        "shipmentType"=> $postData['shipmenType'],
        "shipmentMode"=> $postData['shipmentMode'],
        "paymentType"=> $postData['paymentType']

        ];

        $requestBody = json_encode($requestArray);
        $this->_curl->setOption(CURLOPT_HEADER, 0);
        $this->_curl->setOption(CURLOPT_TIMEOUT, 60);
        $this->_curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->_curl->addHeader("Authorization", "Basic ".$authorization);
        $this->_curl->addHeader("Content-Type", "application/json");
        $this->_curl->post($postData['getApiurl'], $requestBody);
        $response = $this->_curl->getBody();
        $data = (json_decode($response, true));
        if ($data == false || !isset($data['rates'])) {
            $this->_messageManager->addError(__('Something went wrong in Kuebix api.'));
              return false;
        }
        return $data;
    }

    /**
     * Get value from system configuration
     *
     * @param String $path
     * @return Array, string
     */

    public function getconfigValue($path)
    {
        return  $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get percentage Value
     *
     * @return String
     */

    public function getPercentageValue()
    {
        return $this->getconfigValue('carriers/kuebixapi/rate_percentage');
    }
    
    /**
     * Post Data Validate
     *
     * @return array
     */

    public function postDataValidate()
    {
        $getApiurl          = $this->getconfigValue('carriers/kuebixapi/api_url');
        $userName           = $this->getconfigValue('carriers/kuebixapi/username');
        $password           = $this->getconfigValue('carriers/kuebixapi/password');
        $clientId           = $this->getconfigValue('carriers/kuebixapi/client_id');
        $paymentType        = $this->getconfigValue('carriers/kuebixapi/payment_type');
        $shipmentMode       = $this->getconfigValue('carriers/kuebixapi/shipment_mode');
        $shipmenType        = $this->getconfigValue('carriers/kuebixapi/shipment_type');
        $OriginPinCode      = $this->getconfigValue('carriers/kuebixapi/origin_pincode');

        if ($getApiurl == '') {
            $this->_messageManager->addError(__('Kuebixapi Url can not be empty.'));
            return false;
        }

        if ($userName == '') {
              $this->_messageManager->addError(__('User Name can not be empty.'));
              return false;
        }

        if ($password == '') {
              $this->_messageManager->addError(__('Password can not be empty.'));
              return false;
        }

        if ($clientId == '') {
              $this->_messageManager->addError(__('Client Id can not be empty.'));
              return false;
        }

        if ($paymentType == '') {
              $this->_messageManager->addError(__('Payment Type can not be empty.'));
              return false;
        }
 
        if ($shipmentMode == '') {
              $this->_messageManager->addError(__('Shipment Mode can not be empty.'));
              return false;
        }

        if ($shipmenType == '') {
              $this->_messageManager->addError(__('Shipment Type can not be empty.'));
              return false;
        }

        if ($OriginPinCode == '') {
              $this->_messageManager->addError(__('Origin Pincode can not be empty.'));
              return false;
        }

        return [

             'getApiurl' =>  $getApiurl,
             'userName' =>  $userName,
             'password' =>  $password,
             'clientId' =>  $clientId,
             'paymentType' =>  $paymentType,
             'shipmentMode' =>  $shipmentMode,
             'shipmenType' =>  $shipmenType,
             'OriginPinCode' =>  $OriginPinCode

        ];
    }

    /**
     * GetLineItems
     *
     * @return array
     */
    public function getLineItems()
    {
        $items = $this->_cart->getQuote()->getAllItems();
        $product = $this->_product->create();
        $attr = $product->getResource()->getAttribute('freight_class')->getOptions();
        $freightClassarray = [];
        if (!empty($attr)) {
            foreach ($attr as $freightClass) {
        
                if ((int)$freightClass->getValue() > 0):
          
                    $freightClassarray[$freightClass->getValue()]=(int)$freightClass->getValue();
                endif;
            }
        }

        $lineItems=[];
        if (!empty($items)) {
            foreach ($items as $item) {

                $product = $product->load($item->getProductId());

                if ((int)$product->getWeight()=='') {
                    $this->_messageManager->addError(__('Product weight can not be empty.'));
                    return false;
                }
                if ((int)$freightClassarray[$product->getFreightClass()]=='') {
                    $this->_messageManager->addError(__('Freight class can not be empty.'));
                    return false;
                }

                $lineItems[]=[
                "weight"=>(int)$product->getWeight(),
                "freightClass"=>$freightClassarray[$product->getFreightClass()]

                ];
            }
        }

        return $lineItems;
    }
}
