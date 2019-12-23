<?php
namespace Vbout\Plugin\Observer;

use Vbout\Plugin\Vbout\services\EcommerceWS;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class UpdateOrder implements ObserverInterface
{
    /**
     * @param \Psr\Log\LoggerInterface $_logger
     */
    protected $helper;
    protected $_checkoutSession;
    private $logger;

    public function __construct(
        //Cart
        \Magento\Checkout\Model\Session $checkoutSession,
        // Helper
        \Vbout\Plugin\Helper\Data $helper,
        \Psr\Log\LoggerInterface $logger,


        array $data = []
    )
    {
        //Helper
        $this->helper = $helper;
        $this->logger = $logger;
        $this->_checkoutSession = $checkoutSession;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $helper = $this->helper;
        $authTokens = $helper->getAuthTokens();
        $vboutApp = new EcommerceWS($authTokens);
        if ($helper->getAbandonedCart() == 1)
        {

            $discountTotal = '0.0';
            $order = $observer->getEvent()->getOrder();

            if ($order['discount_amount'] != 0 )
                $discountTotal = $order['discount_amount'];

            $orderData = array(
//                'ipaddress' => $_SERVER['HTTP_X_FORWARDED_FOR'],
                "domain" => $helper->getDomain(),
                "orderid" => $order['increment_id'],
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
                "status" => $order->getStatus(),
                "notes" => $order['customer_note'],
                "storename" =>   $order['store_name'],
                "customerinfo" => array(
                    "firstname" => $order['customer_firstname'],
                    "lastname" => $order['customer_lastname'],
                    "email" => $order['customer_email'],
                    "phone" => $order->getShippingAddress()->getTelephone(),
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
                $result = $vboutApp->Order($orderData, 2);
            }
            catch (\Exception $e)
            {}
        }
    }

}