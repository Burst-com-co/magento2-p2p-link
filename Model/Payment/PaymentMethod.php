<?php

namespace Burst\Link\Model\Payment;
use Burst\Link\Helper\Config;
use Magento\Store\Model\ScopeInterface;
/**
 * Pay In Store payment method model
 */
class PaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod
{
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'link';
    
    /**
     * Method is avalaible
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return boolean
     */
    public function isAvailable(
        \Magento\Quote\Api\Data\CartInterface $quote = null) 
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $conf = $objectManager
                ->get('Magento\Framework\App\Config\ScopeConfigInterface')
                ->getValue('payment/burst_link/active');
        $active = (int)$conf;
        if ($active==1) {
            return true;
        } else {
            return false;
        }
    }   
}