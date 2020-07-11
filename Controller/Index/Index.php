<?php
namespace Burst\Link\Controller\Index;

class Index extends \Magento\Framework\App\Action\Action
{
	protected $_pageFactory;

	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $pageFactory,
		\Burst\Link\Model\PaymentLinkFactory $paymentLinkFactory,
        \Burst\Link\Helper\P2P $P2P)
	{
		$this->_pageFactory = $pageFactory;
		$this->P2P=$P2P;
		$this->paymentLinkFactory=$paymentLinkFactory;
		$this->model = $this->paymentLinkFactory->create();
		return parent::__construct($context);
	}

	public function execute()
	{
		/** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->_pageFactory->create();
		$resultPage->getLayout()->initMessages();
		$resultPage->getLayout()->getBlock('test_index_index')->setName('Jonathan');
		return $resultPage;
	}
}
