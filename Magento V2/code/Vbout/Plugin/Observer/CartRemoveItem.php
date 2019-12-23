<?php

namespace Vbout\Plugin\Observer;

use Vbout\Plugin\Vbout\services\EcommerceWS;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class CartRemoveItem implements ObserverInterface
{
    /**
     * @param \Psr\Log\LoggerInterface $_logger
     */
    protected $helper;
    protected $_cart;
    protected $_checkoutSession;
    private $logger;
    protected $storeManager;
    protected $_productRepository;

    public function __construct(
        //Cart
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Model\ProductRepository $productRepository,

        // Helper
        \Vbout\Plugin\Helper\Data $helper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Store\Model\StoreManagerInterface $storeManager,


        array $data = []
    )
    {
        //Helper
        $this->helper = $helper;
        $this->_cart = $cart;
        $this->_checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->_productRepository = $productRepository;


    }


    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->create('Magento\Customer\Model\Session');
        $event = $observer->getEvent();
        if( ! $customerSession->isLoggedIn())
        {
            $customer = $event->getCustomer();
            if(empty((array) $customer))
                $email = '';
            else  $email = $customer->getEmail();

        }
        else
            $email = $customerSession->getCustomer()->getEmail();

        $helper = $this->helper;
        $action = 1;
        $authTokens = $helper->getAuthTokens();
        if (is_array($authTokens)) {
            if ($helper->getAbandonedCart() == 1) {
                $vboutApp = new EcommerceWS($authTokens);
//                $cart = Mage::getModel('checkout/session')->getQuote();
                $cartD = $this->_checkoutSession->getQuote();

                $clearCartData = array(
                    "domain"        => $helper->getDomain(),
                    "cartid"        => $cartD->getId(),
                );
                try{
                    $result = $vboutApp->Cart($clearCartData, 4);
                }
                catch (\Exception $e)
                {}
                $itemQty = 0;

//                $this->logger->debug(json_encode($cartD));

                if ($cartD->getItemsQty() > 0) {

//                    $store = array(
//                        "domain" => $helper->getDomain(),
//                        "cartcurrency"  => $this->storeManager->getStore()->getCurrentCurrency()->getCode(),
//                        "cartid"        => $cartD->getId(),
//                        'ipaddress'     => $_SERVER['REMOTE_ADDR'],
//                        "customer"      => $email,
//                        "storename"     => $this->storeManager->getStore()->getFrontendName(),
//                        "uniqueid"      => $helper->userSessionId(),
//
////                            "abandonurl"    => "https://johnny.gloclick.com/cart/"
//                    );
//
//                    $result = $vboutApp->Cart($store, $action);
                    $products = $cartD->getAllItems();
                    foreach ($products as $productQ)
                    {
                        $itemQty +=1;
                        $product = $productQ->getProduct();
                        $priceVariations = 0;

                        //selected variations
                        $variations = array();
                        $options =  $productQ->getProduct()->getTypeInstance(true)->getOrderOptions($productQ->getProduct());

                        if(array_key_exists('options', $options)) {
                            $options = $options['options'];
                            foreach ($options as $option) {
                                $optionTitle = $option['label'];
                                $optionId = $option['option_id'];
                                $optionType = $option['option_type'];
                                $optionValue = $option['value'];
                                if ($optionType === 'drop_down') {
                                    $variations[$option['label']] = $optionValue;
                                }
                            }
                        }
                            //                        $options = $product->getOptions();
//                        foreach ($options as $option) {
//                            if ($option->getType() === 'drop_down') {
//                                $values = $option->getValues();
//                                foreach ($values as $value) {
//                                    if (isset($variations[$option->getDefaultTitle()])) {
//                                        if ($variations[$option->getDefaultTitle()] == $value->getTitle()) {
//                                            $priceVariations = $priceVariations + (double)$value->getPrice();
//                                        }
//                                    }
//                                }
//                            }
//                        }
                        if($product->getSpecialPrice() != 0 )
                            $discountPrice = $product->getSpecialPrice();
                        else $discountPrice = '0.0';
                        if (isset($product->getCategoryIds()[0]) && $product->getCategoryIds()[0]  != '')
                        {
                            $categoryId = $product->getCategoryIds()[0];
                            $categoryName = $objectManager->create('Magento\Catalog\Model\Category')->load($product->getCategoryIds()[0])->getName();

                        }
                        else
                        {
                            $categoryName = 'N/A';
                            $categoryId = 'N/A';
                        }
                        $productImage = $this->_productRepository->getById($product->getId());
                        $productImage = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $productImage->getImage();

                        $productData = array(
                            "productid"     => $product->getId(),
                            "cartid"        => $cartD->getId(),
                            "name"          => $product->getName(),
                            "price"         => (float)$product->getFinalPrice(),
                            "description"   => $product->getDescription(),
                            "discountprice" =>  $discountPrice,
                            "currency"      => $this->storeManager->getStore()->getCurrentCurrency()->getCode(),
                            "sku"           => $product->getSku(),
                            "quantity"      => $productQ->getQty(),
                            "categoryid"    => $categoryId,
                            "category"      => $categoryName,
                            "link"          => $product->getProductUrl(),
                            "variation"     => $variations,
                            "uniqueid"      => $helper->userSessionId(),
                            "image"         => $productImage,
                            'domain'        => $helper->getDomain(),
                        );
                        $result  = $vboutApp->CartItem($productData,1);
                    }
                }
                if($itemQty == 0){
                    $clearCartData = array(
                        "domain"        => $helper->getDomain(),
                        "cartid"        => $cartD->getId(),
                    );
                    try{
                        $result = $vboutApp->Cart($clearCartData, 3);
                    }
                    catch (\Exception $e)
                    {}
                }

            }
        }
    }

}