<?php
namespace Vbout\Plugin\Observer;

use Vbout\Plugin\Vbout\services\EcommerceWS;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;

class ProductSearch implements ObserverInterface
{
    /**
     * @param \Psr\Log\LoggerInterface $_logger
     */
    protected $helper;
    private $logger;
    private $remoteAddress;

    public function __construct(

        // Helper
        \Vbout\Plugin\Helper\Data $helper,
        \Psr\Log\LoggerInterface $logger,
        RemoteAddress $remoteAddress,

        array $data = []
    )
    {
        //Helper
        $this->helper = $helper;
        $this->logger = $logger;
        $this->remoteAddress = $remoteAddress;

    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        //only enter if there is query search
        if (isset($_GET['q'])) {
            $event = $observer->getEvent();
            $helper = $this->helper;
            if ($helper->getSearch() == 1) {
                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $customerSession = $objectManager->create('Magento\Customer\Model\Session');
                    if (! $customerSession->isLoggedIn())
                        $email = '';
                    else
                        $email = $customerSession->getCustomer()->getEmail();
                    $query = $_GET['q'];
                    $authTokens = $helper->getAuthTokens();
                    $vboutApp = new EcommerceWS($authTokens);
                    $searchPayload = array(
                        'domain' => $helper->getDomain(),
                        'customer' => $email,
                        'query' => $query,
                        "uniqueid" => $helper->userSessionId(),
                        'ipaddress' => $_SERVER['HTTP_X_FORWARDED_FOR'],
                    );
                try{
                    $result = $vboutApp->sendProductSearch($searchPayload);
                }
                catch (\Exception $e)
                {}

            }
        }
    }

}