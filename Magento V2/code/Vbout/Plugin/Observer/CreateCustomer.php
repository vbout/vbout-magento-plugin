<?php
namespace Vbout\Plugin\Observer;

use Vbout\Plugin\Vbout\services\EcommerceWS;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class CreateCustomer implements ObserverInterface
{
    /**
     * @param \Psr\Log\LoggerInterface $_logger
     */
    protected $helper;
    private $logger;

    public function __construct(

        // Helper
        \Vbout\Plugin\Helper\Data $helper,
        \Psr\Log\LoggerInterface $logger,


        array $data = []
    )
    {
        //Helper
        $this->helper = $helper;
        $this->logger = $logger;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $helper = $this->helper;
        $authTokens = $helper->getAuthTokens();
        $vboutApp = new EcommerceWS($authTokens);
        $customer = $observer->getEvent()->getCustomer();

        $email = $customer->getEmail();
        if ($email) {
            if (is_array($authTokens)) {
                if ($helper->getCustomers() == 1) {
                    $customerData = array(
                        "firstname" => $customer->getFirstname(),
                        "lastname" => $customer->getLastname(),
                        "email" => $email,
                        'domain' => $helper->getDomain(),
                        'ipaddress' => $_SERVER['HTTP_X_FORWARDED_FOR'],
                        "uniqueid" => $helper->userSessionId(),
                    );
                    try{
                        $result = $vboutApp->Customer($customerData, 1);
                    }
                    catch (\Exception $e)
                    {}
                }
            }
        }
    }

}