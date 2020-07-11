<?php
namespace Burst\Link\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use SoapClient;

class P2P extends AbstractHelper{
    protected $logger, $scopeConfig, $_mail, $_login, $_tranKey, $_seed, $_ws,
        $_transactionArray, $_reference, $_description, $_document, $_name, $_serviceCode, 
        $_serviceName, $_currency, $_totalAmount, $_taxAmount, $_devolutionBase, 
        $_creationDate, $_dueDate, $_dueRate, $_dueType, $_cutDate, $_referenceAlt, $_phone,
        $_soapClient, $_trankey, $_randomNonce, $_createdDate, $_password, $_requestArray, 
        $_lastName, $_soapHeader, $_token, $_security, $_requestID, $_response, $_p2p_url;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Burst\Link\Helper\Config $config,
        \Burst\Link\Model\PaymentLinkFactory $paymentLinkFactory,
        \Psr\Log\LoggerInterface $logger
    )
	{
        $this->scopeConfig = $scopeConfig;
        $this->paymentLinkFactory = $paymentLinkFactory;
        $this->_transportBuilder = $transportBuilder;
        $this->config = $config;
        $this->logger = $logger;
        $this->_login = $this->config->getLogin();
        $this->_seed = date('c');
        $this->_trankey = $this->config->getTranKey();
        $this->setRandomNonce();
        $this->_createdDate = date('c');
        $this->_password = base64_encode(sha1($this->_randomNonce . $this->_createdDate . $this->_trankey, true));
    }
    public function createLink($name, $lastname, $document, $mail, $phone, $reference, $totalAmount)
    {
        $this->_name=$name;
        $this->_mail=$mail;
		$this->_lastName=$lastname;
		$this->_document=$document;
		$this->_phone=$phone;
		$this->_reference=$reference;
        $this->_totalAmount=$totalAmount;
        $array_params=[
            'name'=>$this->_name,
            'lastName'=>$this->_lastName,
            'document'=>$this->_document,
            'mail'=>$this->_mail,
            'phone'=>$this->_phone,
            'reference'=>$this->_reference,
            'totalAmount'=>$this->_totalAmount,
        ];
        $this->connectToSoapServer();
        $this->createRequestArray();
        $this->setSoapHeader();
        $this->createPaymentRequest();
        // $this->finishRequest();
    }
    private function connectToSoapServer(){
        try {
            $this->_soapClient = new \SoapClient($this->config->getDefaultEndpoint().'?wsdl',
                array('location' => $this->config->getDefaultEndpoint()));
        } catch (Exception $e) {
            $this->logger->addInfo('P2P LINK', ["Error"=>json_encode($e->getMessage())]);
        }
    }
    private function setRandomNonce() {
        if (function_exists('random_bytes')) {
            $this->_randomNonce = bin2hex(random_bytes(16));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $this->_randomNonce = bin2hex(openssl_random_pseudo_bytes(16));
        } else {
            $this->_randomNonce = mt_rand();
        }
    }
    private function createRequestArray() {
        $this->_requestArray = [
            'locale' => 'es_CO',
            'buyer' => [
                'documentType' => 'CC',
                'document' => "$this->_document",
                'name' => "-",
                'surname' => "$this->_lastName",
                'email' => "$this->_mail",
                'mobile' => "$this->_phone"
            ],
            'payment' => [
                'reference' => "$this->_reference",
                'description' => 'Productos para mascota',
                'amount' => [
                    'currency' => 'COP',
                    'total' => "$this->_totalAmount",
                ],
                'allowPartial' => 0
            ],
            'expiration' => date('c', strtotime('+'.$this->config->getExpirationTime().' day')),
            'returnUrl' => $this->config->getDefaultStoreUrl(),
            'ipAddress' => $_SERVER['REMOTE_ADDR'],
            'userAgent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'CLIENT_USER_AGENT'
        ];
    }
    private function setSoapHeader() {
        try {
            $this->_token = new \stdClass;
            $this->_token->Username = new \SoapVar($this->_login, XSD_STRING, NULL, 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd', NULL, 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd');
            $this->_token->Password = new \SoapVar($this->_password, XSD_STRING, 'PasswordDigest', NULL, 'Password', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd');
            $this->_token->Nonce = new \SoapVar(base64_encode($this->_randomNonce), XSD_STRING, null, 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd', null, 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd');
            $this->_token->Created = new \SoapVar($this->_createdDate, XSD_STRING, NULL, 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd', null, 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd');
            $this->_security = new \stdClass;
            $this->_security->UsernameToken = new \SoapVar($this->_token, SOAP_ENC_OBJECT, NULL, 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd', 'UsernameToken', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd');
            $this->_soapHeader = new \SoapHeader('http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd', 'Security', $this->_security, true);
            
        } catch (Exception $e) {
            $this->logger->addInfo('P2P LINK', ["Error"=>json_encode($e->getMessage())]);
        }
    }
    private function createPaymentRequest(){
        $this->_requestID = null;
        try {
            $this->_soapClient->__setSoapHeaders($this->_soapHeader);
            $this->_response = $this->_soapClient->createRequest(array(
                'payload' => $this->_requestArray
            ));
            $array = json_decode(json_encode($this->_response), True);
            $this->logger->addInfo('P2P LINK', ["Error"=>$array]);
             $soap_response=$array;
            
            if ($this->_response) {
                if ($soap_response["createRequestResult"]["status"]["status"] == 'OK') {
                    $this->_requestID = $soap_response["createRequestResult"]["requestId"];
                    $this->_p2p_url= $soap_response["createRequestResult"]["processUrl"];
                    $this->sendEmail();
                    
                    $data=[
                        'increment_id'=>$this->_reference,
                        'amount'=>$this->_totalAmount,
                        'customer_email'=>$this->_mail,
                        'customer_firstname'=>$this->_lastName,
                        'status'=>'CREATED',
                        'requestId'=>$this->_requestID,
                        'payment_url'=>$this->_p2p_url,
                        'valid_until'=>$this->_requestArray['expiration']

                    ];
                    $this->insertRecord($data);
                } else {

                }
            } else {
                $this->logger->addInfo('P2P LINK', ["Error"=>json_encode("Error al conectar")]);
            }
            
        } catch (Exception $e) {
            $this->logger->addInfo('P2P LINK', ["Error"=>json_encode($e->getMessage())]);
        }
    }
    function get_response() {
        return $this->_response;
    }
    private function finishRequest() {
        $array = json_decode(json_encode($this->_response), True);
        $soap_response=$array;
        if($this->_requestID) {
            try {
                $this->_soapClient->__setSoapHeaders($this->_soapHeader);
                $this->_response = $this->_soapClient->getRequestInformation(array(
                    'requestId' => $this->_requestID
                ));
                if ($this->_response) {
                    $this->_response = $this->_response->getRequestInformationResult;
                    if ($this->_response->status->status == 'OK') {
                    }
                } else {
                    // Error al conectar
                }
            } catch (Exception $e) {
                $this->logger->addInfo('P2P LINK', ["Error"=>json_encode($e->getMessage())]);
            }
        }
    }
    public function sendEmail()
    {
        try {
            $sentToEmail = $this->_mail;
            $sentToName = $this->_name;
            $sender = [
                'name' => $this->config->getStorename(),
                'email' => $this->config->getStoreEmail()
            ];
            $this->mail($sender, $sentToEmail, $sentToName);
            if (!\is_null($this->config->getCopyAddressEmail())) {
                $this->mail($this->config->getCopyAddressEmail(),'Seller');
            }
        } catch (Exception $e) {
            $this->logger->addInfo('P2P LINK', ["Error"=>json_encode($e->getMessage())]);
        }
    }
    public function mail($sender, $sentToEmail, $sentToName)
    {
        $transport = $this->_transportBuilder
            ->setTemplateIdentifier('burst_link_custom_email_template')
            ->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND, /* here you can defile area and store of template for which you prepare it */
                    'store' => $this->config->getStoreID(),
                ]
            )
            //Email template variables
            ->setTemplateVars(
                [
                    'name'=> $this->_name,
                    'store_name'=>$this->config->getStorename(),
                    'reference'=>$this->_reference,
                    'process_url'=>$this->_p2p_url,
                    'email_subject'=>$this->config->getEmailSubject(),
                ]
            )
            ->setFrom($sender)
            ->addTo($sentToEmail, $sentToName)
            ->getTransport();
        $transport->sendMessage();
    }
    public function insertRecord($data)
    {
        $model = $this->paymentLinkFactory->create();
		$model->addData($data);
        $saveData = $model->save();
    }
    public function getPaymentStatus($order, $amount)
    {
        $this->soap_client= new SoapClient("https://api.placetopay.com/soap/placetopay/?wsdl",
            ['trace' => 1]);
        $lastAction=$this->getLastPaymentAction($order, $amount);
        return $lastAction;

    }
    /**
     * Create a new request to review the payment review 
     * @return type
     */

    private function getLastPaymentAction($order, $amount){
        $array_query= $this->getArrayToSoapQuery($order, $amount);
        //return $array_query;
        $soapQuery = $this->soap_client->queryTransaction($array_query);
        
        $soapResponse=$soapQuery->queryTransactionResult;
        $response=null;
        if (isset($soapResponse->item)){
            $soapToArray= json_decode(json_encode($soapResponse->item),TRUE);
            if (isset($soapToArray[0]) && is_array($soapToArray[0])) {
                //Si tiene varios intentos la respuesta mas reciente queda en la posicion cero
                $response=[
                    "order"=>"$order",
                    "responseCode"=>$soapToArray[0]["responseCode"],
                    "requestDate"=>$soapToArray[0]["requestDate"],
                    "response"=>$soapToArray[0]["transactionState"],
                    "responseText"=>$soapToArray[0]["responseReasonText"],
                    "franchise"=>$soapToArray[0]["franchiseName"]
                ];
            }else{
                $response=[
                    "order"=>"$order",
                    "responseCode"=>$soapToArray["responseCode"],
                    "requestDate"=>$soapToArray["requestDate"],
                    "response"=>$soapToArray["transactionState"],
                    "responseText"=>$soapToArray["responseReasonText"],
                    "franchise"=>$soapToArray["franchiseName"]];
            }
        }
        return $response;
    }
    /**
     * Create a data structure to Soap request 
     * @param type $order
     * @param type $amount
     * @param type $x_login
     * @param type $trankey
     * @return type
     */

    private function getArrayToSoapQuery($order, $amount) {
        
        $array_query=[
   			'auth' => [
               'login' => $this->config->getLogin(),
               'tranKey' => sha1($this->_seed. $this->_trankey),
               'seed' => $this->_seed
           	],
           	'request' => [
               'reference' => $order,
               'currency' => 'COP',
               'totalAmount' => $amount
           	]
        ];
        return $array_query;
    }
    
}