<?php
namespace Burst\Link\Model\ResourceModel\PaymentLink;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'Burst\Link\Model\PaymentLink',
            'Burst\Link\Model\ResourceModel\PaymentLink'
        );
    }
}