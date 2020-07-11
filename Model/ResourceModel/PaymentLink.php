<?php

namespace Burst\Link\Model\ResourceModel;

class PaymentLink extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function __construct(
		\Magento\Framework\Model\ResourceModel\Db\Context $context
	)
	{
		parent::__construct($context);
	}
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('burst_p2p_payment_link', 'id');
    }
}