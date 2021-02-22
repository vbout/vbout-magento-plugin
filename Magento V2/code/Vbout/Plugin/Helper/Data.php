<?php

namespace Vbout\Plugin\Helper;
use Vbout\Plugin\Vbout\services\EcommerceWS;

//use Magento\Framework\App\Helper\AbstractHelper as ;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_GENERAL = 'vbout/general_settings/';
    const XML_PATH_API = 'vbout/api_settings/';
    const XML_PATH_EM = 'vbout/em_settings/';
    const XML_PATH_TRACKING = 'vbout/tracking_settings/';
    const XML_PATH_General = 'vbout/general/';
    private $logger;
    //Configuration
    protected $scopeConfig;
    protected $storeManager;
    //Products
    protected $_productCollectionFactory;
    protected $productStatus;
    protected $productVisibility;
    //Customers
    protected $_customerCollectionFactory;
    //Cookie
    private $cookieManager;
    private $cookieMetadataFactory;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Psr\Log\LoggerInterface $logger,
        //Cookie
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        //Customer
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory ,


        //product
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory ,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
        \Magento\Catalog\Model\Product\Visibility $productVisibility

    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->_customerCollectionFactory = $customerCollectionFactory;
        $this->logger = $logger;
        //Product
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->productStatus = $productStatus;
        $this->productVisibility = $productVisibility;

        //Cookie
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;

    }


    public function getApiSettings($code, $storeId = null)
    {
        if ($code == 'api_key')
        {
            return $this->scopeConfig->getValue('vbout/general/api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeManager->getStore()->getStoreId());
        }
        else
            return $this->scopeConfig->getValue(self::XML_PATH_API . $code, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeManager->getStore()->getStoreId());
    }

    public function getAuthTokens()
    {
        $authTokens = 0;
        if ($this->getApiSettings('api_key')) {
            $authTokens = array('api_key' => $this->getApiSettings('api_key'));

        }
        return $authTokens;
    }

    //Get Domain Name
    public function getDomain()
    {
        $domain = '0';
        if ($this->getApiSettings('domain')) {
            $domain = $this->getApiSettings('domain');
        }
        return $domain;
    }


    public function getAbandonedCart()
    {
        $abandonedCart = 0;
        if ($this->getApiSettings('abandoned_carts')) {
            $abandonedCart = $this->getApiSettings('abandoned_carts');
        }
        return $abandonedCart;
    }

    public function getSearch()
    {
        $search = 0;
        if ($this->getApiSettings('search')) {
            $search = $this->getApiSettings('search');
        }
        return $search;
    }

    public function getProductVisits()
    {
        $product_visits = 0;
        if ($this->getApiSettings('product_visits')) {
            $product_visits = $this->getApiSettings('product_visits');
        }
        return $product_visits;
    }

    public function getCategoryVisits()
    {
        $category_visits = 0;
        if ($this->getApiSettings('category_visits')) {
            $category_visits = $this->getApiSettings('category_visits');
        }
        return $category_visits;
    }

    public function getCustomers()
    {
        $customers = 0;
        if ($this->getApiSettings('customers')) {
            $customers = $this->getApiSettings('customers');
        }
        return $customers;
    }

    public function getCurrentCustomers()
    {
        $current_customers = 0;
        if ($this->getApiSettings('current_customers')) {
            $current_customers = $this->getApiSettings('current_customers');
        }
        return $current_customers;
    }

    public function getProductFeed()
    {
        $product_feed = 0;
        if ($this->getApiSettings('product_feed')) {
            $product_feed = $this->getApiSettings('product_feed');
        }
        return $product_feed;
    }

    public function getSyncCurrentProducts()
    {
        $sync_current_products = 0;
        if ($this->getApiSettings('sync_current_products')) {
            $sync_current_products = $this->getApiSettings('sync_current_products');
        }
        return $sync_current_products;
    }

    public function userSessionId()
    {
        $sessionId = $this->cookieManager->getCookie(
            'vbtEcommerceUniqueId'
        );
        return $sessionId;
    }

    //Sync All Customers
    public function syncCurrentCustomers($authTokens, $domain)
    {
        $starttime = time();
        $vboutApp = new EcommerceWS($authTokens);
        $page = 1;

        try{
            do {
                $users = $this->_customerCollectionFactory->create()
                    ->addAttributeToSelect('firstname')
                    ->addAttributeToSelect('lastname')
                    ->addAttributeToSelect('email')
                    ->setPageSize(10)
                    ->setCurPage($page);

                if ($users->count() > 0) {
                    foreach ($users as $user) {
                        $customer = array(
                            "firstname" => $user->getFirstname(),
                            "lastname" => $user->getLastname(),
                            "email" => $user->getEmail(),
                            'domain' => $domain,
                            'ipaddress' => $_SERVER['REMOTE_ADDR']
                        );
                        $vboutApp->Customer($customer, 1);
                    }
                    $page++;
                    $users->clear();
                }

                $now = time()-$starttime;

            } while ($users->count() > 0 && $now < 30);
        } catch (\Exception $e) {}
    }

    //Sync All Products
    public function syncCurrentProducts($authTokens,$domain)
    {
        $starttime = time();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $vboutApp = new EcommerceWS($authTokens);
        $page = 1;

        try{
            do {
                $productCollection = $this->_productCollectionFactory->create()
                ->addAttributeToSelect('*')
                ->setPageSize(10)
                ->setCurPage($page);

                if($productCollection->count() > 0) {
                    foreach ($productCollection as $product) {

                        //Get variations
                        $variation = array();
                        $customOptions = $objectManager->get('Magento\Catalog\Model\Product\Option')->getProductOptionCollection($product);
                        foreach($customOptions as $key=>$o) {
                            $optionType = $o->getType();
                            if ($optionType == 'drop_down') {
                                $values = $o->getValues();
                                $countVariations = 0 ;
                                $variations = '';
                                foreach ($values as $key=>$v) {
                                    $variations .= $v->getTitle();
                                    if($countVariations < count($values)-1)
                                        $variations .=', ';
                                    $countVariations++;
                                }
                                $variation[$o->getTitle()] = $variations;
                            }
                        }

                        //Get Discount
                        if ($product->getPrice() != $product->getFinalPrice()){
                            $discountPrice = $product->getFinalPrice();
                        }
                        else $discountPrice = '0.0';

                        //Get Category
                        if($product->getCategoryIds()[0] != ''){
                            $categoryName = $objectManager->create('Magento\Catalog\Model\Category')->load($product->getCategoryIds()[0])->getName();
                        }
                        else $categoryName = 'N/A';

                        $productData = array(
                            "sync"          => true,
                            "productid"     => $product->getId(),
                            "name"          => $product->getName(),
                            "price"         => (float)$product->getPrice(),
                            "description"   => $product->getDescription(),
                            "discountprice" =>  $discountPrice,
                            "currency"      => $this->storeManager->getStore()->getCurrentCurrency()->getCode(),
                            "sku"           => $product->getSku(),
                            "categoryid"    => $product->getCategoryIds()[0],
                            "category"      => $categoryName,
                            "variation"     => $variation,
                            "link"          => $product->getProductUrl(),
                            "image"         => $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage(),
                            'domain'        => $domain,
                        );

                        $vboutApp->Product($productData,1);

                    }
                    $page++;
                    $productCollection->clear();
                }

                $now = time()-$starttime;

            } while ($productCollection->count() > 0 && $now < 30);
        } catch (\Exception $e) {}
    }
}

