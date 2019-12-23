<?php
namespace Vbout\Plugin\Observer;

use Vbout\Plugin\Vbout\services\EcommerceWS;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class CreateOrder implements ObserverInterface
{
    /**
     * @param \Psr\Log\LoggerInterface $_logger
     */
    protected $helper;
    protected $_checkoutSession;
    private $logger;
    protected $orderRepository;

    public function __construct(
        //Cart
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,

        // Helper
        \Vbout\Plugin\Helper\Data $helper,
        \Psr\Log\LoggerInterface $logger,


        array $data = []
    )
    {
        //Helper
        $this->helper = $helper;
        $this->_checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;

    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $helper = $this->helper;
        $authTokens = $helper->getAuthTokens();
        $vboutApp = new EcommerceWS($authTokens);
        if ($helper->getAbandonedCart() == 1)
        {
            $order = $observer->getEvent()->getOrder();

            $discountTotal = '0.0';
            //checkout without logging in
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $customerSession = $objectManager->create('Magento\Customer\Model\Session');
            if (! $customerSession->isLoggedIn())
            {
                $customerData = array(
                    "firstname" => $order['customer_firstname'],
                    "lastname" => $order['customer_lastname'],
                    "email" => $order['customer_email'],
                    "phone" => $order->getBillingAddress()->getTelephone(),
                    'domain' => $helper->getDomain(),
                    'ipaddress' => $_SERVER['HTTP_X_FORWARDED_FOR'],
                    "uniqueid" => $helper->userSessionId(),
                );
                try{
                    $result = $vboutApp->Customer($customerData, 1);
                }
                catch (\Exception $e)
                {}
                //update cart
                $store = array(
                    "domain" => $helper->getDomain(),
                    "cartcurrency"  => $order['order_currency_code'],
                    "cartid"        => $order['quote_id'],
                    'ipaddress'     => $_SERVER['HTTP_X_FORWARDED_FOR'],
                    "email"         => $order['customer_email'],
                    "storename"     => $order['store_name'],
                    "uniqueid"      => $helper->userSessionId(),

//                            "abandonurl"    => "https://johnny.gloclick.com/cart/"
                );
                try
                {
                    $result = $vboutApp->Cart($store, 2);
                } catch (\Exception $e)
                    {}
            }

            if ($order['discount_amount'] != 0 )
                $discountTotal = $order['discount_amount'];

            $orderData = array(
                "cartid" => $order['quote_id'],
                "uniqueid"      => $helper->userSessionId(),
                "domain" => $helper->getDomain(),
                'ipaddress' => $_SERVER['HTTP_X_FORWARDED_FOR'],
                "orderid" => $order['increment_id'],
//                    "paymentmethod" => $order['shipping_description'],
                "paymentmethod" => $order->getPayment()->getMethod(),
                "grandtotal" => $order->getGrandTotal(),
                "orderdate" => strtotime(date('Y-m-d H:i:s')),
                "shippingmethod" => $order['shipping_description'],
                "shippingcost" => $order['shipping_amount'],
                "subtotal" => $order['subtotal'],
                "discountvalue" => $discountTotal,
                "taxcost" => $order['tax_amount'],
//                    "otherfeecost" => $order->get_fees(),
                "currency" => $order['order_currency_code'],
                "status" => $observer->getEvent()->getOrder()->getStatus(),
                "notes" => $order['customer_note'],
                "storename" =>   $order['store_name'],
                "customerinfo" => array(
                    "firstname" => $order['customer_firstname'],
                    "lastname" => $order['customer_lastname'],
                    "email" => $order['customer_email'],
                    "phone" => $order->getBillingAddress()->getTelephone(),
                ),
                "billinginfo" => array(
                    "firstname" => $order->getBillingAddress()->getFirstname(),
                    "lastname" =>  $order->getBillingAddress()->getLastname(),
                    "email" => $order->getBillingAddress()->getEmail(),
                    "phone" => $order->getBillingAddress()->getTelephone(),
//                        "company" => $order->shipping_company,
                    "address" => $order->getBillingAddress()->getTelephone(),
                    "address2" => $order->getBillingAddress()->getTelephone(),
                    "city" => $order->getBillingAddress()->getCity(),
                    "statename" => $order->getBillingAddress()->getRegionId(),
                    "countryname" => $order->getBillingAddress()->getCountryId(),
                    "zipcode" => $order->getBillingAddress()->getPostcode(),
                ),
                "shippinginfo" => array(
                    "firstname" => $order->getShippingAddress()->getFirstname(),
                    "lastname" =>  $order->getShippingAddress()->getLastname(),
                    "email" => $order->getShippingAddress()->getEmail(),
                    "phone" => $order->getShippingAddress()->getTelephone(),
//                        "company" => $order->shipping_company,
                    "address" => $order->getShippingAddress()->getTelephone(),
                    "address2" => $order->getShippingAddress()->getTelephone(),
                    "city" => $order->getShippingAddress()->getCity(),
                    "statename" => $order->getShippingAddress()->getRegionId(),
                    "countryname" => $order->getShippingAddress()->getCountryId(),
                    "zipcode" => $order->getShippingAddress()->getPostcode(),
                )
            );
            try{
                $result = $vboutApp->Order($orderData, 1);
            }
            catch (\Exception $e)
            {}
        }
    }

}