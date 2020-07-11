<?php
namespace Burst\Link\Cron;

class PaymentLInk
{
    protected $logger;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Burst\Link\Model\PaymentLinkFactory $paymentLinkFactory,
        \Burst\Link\Helper\P2P $P2P) {
        $this->logger = $logger;
        $this->P2P=$P2P;
        $this->paymentLinkFactory=$paymentLinkFactory;
    }

    public function execute(){
        $model = $this->paymentLinkFactory->create();
        $collection = $model->getCollection()
            ->addFieldToFilter('valid_until', ['gteq' => date('Y-m-d H:i:s')])
            ->addFieldToFilter('status', ['eq' => 'CREATED']);

        foreach($collection as $item){
            $data=$item->getData();
            $payment_status=$this->P2P->getPaymentStatus($data["increment_id"], $data["amount"]);
            if (!is_null($payment_status)) {
                $update = $model->load($data["id"]);
                $update->setStatus($payment_status["response"]);
                $update->save();
            }
        }
    }
}
