<?php

namespace Burst\Link\Model;

class PaymentLink extends \Magento\Framework\Model\AbstractModel
{
    const CACHE_TAG = 'burst_p2p_payment_link';
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Burst\Link\Model\ResourceModel\PaymentLink');
    }
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}