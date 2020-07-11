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
		$collection = $this->model->getCollection()
			->addFieldToFilter('valid_until', ['gteq' => date('Y-m-d H:i:s')])
			->addFieldToFilter('status', ['eq' => 'CREATED']);
		foreach($collection as $item){
			$data=$item->getData();
			$payment_status=$this->P2P->getPaymentStatus($data["increment_id"], $data["amount"]);
		}
		$resultPage->getLayout()->getBlock('test_index_index')->setName('Jonathan');
		$resultPage->getLayout()->getBlock('test_index_index')->setOrders($collection);
		return $resultPage;
	}
}
