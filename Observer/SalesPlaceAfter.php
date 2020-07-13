<?php
namespace Burst\Link\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;

class SalesPlaceAfter implements ObserverInterface
{
    //Order Variables
    private $order , $reference, $alt_reference, $payment, $payment_code, $total_amount, $tax_amount;
    //Customer Variables
    private $customer_id, $name, $last_name, $document, $mail, $phone;
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager, $P2P;
    private $logger, $login, $trankey, $expiration;
    
    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager, 
        \Psr\Log\LoggerInterface $logger,
        \Burst\Link\Helper\P2P $P2P,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Burst\Link\Helper\Config $config) {

        $this->_objectManager = $objectManager;
        $this->P2P = $P2P;
        $this->config = $config;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->_transportBuilder = $transportBuilder;
        $this->_storeManager = $storeManager;
        $this->login=$this->scopeConfig->getValue('payment/burst_link/login', ScopeInterface::SCOPE_STORE);
        $this->trankey=$this->scopeConfig->getValue('payment/burst_link/trankey', ScopeInterface::SCOPE_STORE);
        $this->expiration=$this->scopeConfig->getValue('payment/burst_link/expiration', ScopeInterface::SCOPE_STORE);
    }
    /**
     * customer register event handler
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->defineVariablesToLink($observer);
        if($this->payment_code=='link'){
            $this->P2P->createLink($this->name, $this->lastname, $this->document, $this->mail, 
                $this->phone, $this->reference, $this->total_amount);
        }
    }
    private function defineVariablesToLink($observer){
        //Order Data
        $this->order = $observer->getEvent()->getOrder();
        $this->payment = $this->order->getPayment();
        $this->payment_code=$this->payment->getMethodInstance()->getCode();
        $this->reference = $this->order->getIncrementId();
        $this->alt_reference = $this->order->getEntityId();
        $this->total_amount=ceil($this->order->getGrandTotal());
        $this->tax_amount=$this->order->getTaxAmount();
        //Customer Data
        $this->customer_id = $this->order->getCustomerId();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customer = $objectManager->create('Magento\Customer\Model\Customer')->load($this->customer_id);
        $this->mail=$customer->getEmail();
        $this->name=$customer->getName();
        $this->document=$customer->getCedula();
        $this->lastname='';
        $this->phone = $observer->getOrder()->getBillingAddress()->getTelephone();
    }
}