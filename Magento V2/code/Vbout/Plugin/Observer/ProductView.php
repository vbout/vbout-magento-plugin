<?php
namespace Vbout\Plugin\Observer;

use Vbout\Plugin\Vbout\services\EcommerceWS;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class ProductView implements ObserverInterface
{
    /**
     * @param \Psr\Log\LoggerInterface $_logger
     */
    protected $helper;
    private $logger;
    protected $_productRepository;
    protected $storeManager;

    public function __construct(

        // Helper
        \Vbout\Plugin\Helper\Data $helper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,


        array $data = []
    )
    {
        $this->_productRepository = $productRepository;
        $this->storeManager = $storeManager;

        //Helper
        $this->helper = $helper;
        $this->logger = $logger;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $helper = $this->helper;
        $authTokens = $helper->getAuthTokens();
        $vboutApp = new EcommerceWS($authTokens);
        //User Email if logged in

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->create('Magento\Customer\Model\Session');
        if (! $customerSession->isLoggedIn())
            $email = '';
        else
        {
            $customer = $customerSession->getCustomer();
            $email = $customerSession->getCustomer()->getEmail();
        }
        $product_id = $observer->getEvent()->getProduct()->getId();
        $product = $this->_productRepository->getById($product_id);
        if ($helper->getProductVisits() == 1) {
            //Get variations
//            $variation = array();
//            foreach($product->getOptions() as $key=>$o) {
//                $optionType = $o->getType();
//                if ($optionType == 'drop_down') {
//                    $values = $o->getValues();
//                    $countVariations = 0 ;
//                    $variations = '';
//                    foreach ($values as $key=>$v) {
//                        $variations .= $v->getTitle();
//                        if($countVariations < count($values)-1)
//                            $variations .=', ';
//                        $countVariations++;
//                    }
//                    $variation[$o->getTitle()] = $variations;
//                }
//            }
            //Get Discount
            if ($product->getPrice() != $product->getFinalPrice())
                $discountPrice = $product->getFinalPrice();
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
            if ($product->getPrice() == '')
                $price = '0.0';
            else
                $price = $product->getPrice();

            $productData = array(
                "customer"          => $email,
                'ipaddress' => $_SERVER['HTTP_X_FORWARDED_FOR'],
                "productid"         => $product->getId(),
                "name"              => $product->getName(),
                "price"             => $price,
                "description"       => $product->getDescription(),
                "discountprice"     => $discountPrice,
                "currency"          => $this->storeManager->getStore()->getCurrentCurrency()->getCode(),
                "sku"               => $product->getSku(),
                "categoryid"        => $categoryId,
                "category"          => $categoryName,
//                "variation"         => $variation,
                "link"              => $product->getProductUrl(),
                "image"              => $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage(),
                'domain'            => $helper->getDomain(),
                "uniqueid"          => $helper->userSessionId(),

            );
            try{
                $result = $vboutApp->Product($productData, 1);
            }
            catch (\Exception $e)
            {}
            $result = $vboutApp->Product($productData, 1);
        }
        if ($helper->getCategoryVisits() == 1)
        {
            if(count($product->getCategoryIds())>0)
            {
                foreach ( $product->getCategoryIds()as $category_id)
                {
                    $_objectManager = \Magento\Framework\App\ObjectManager::getInstance();

                    $category = $_objectManager->create('Magento\Catalog\Model\Category')->load($category_id);

                    $category = array(
                        "customer"      => $email,
                        "domain"        => $helper->getDomain(),
                        "categoryid"    => $category_id,
                        "name"          => $category->getName(),
                        "link"          => $category->getUrl(),
                        'ipaddress'     => $_SERVER['REMOTE_ADDR'],
                        "uniqueid"      => $helper->userSessionId(),

                    );
                    try{
                        $result = $vboutApp->Category($category, 1);
                    }
                    catch (\Exception $e)
                    {}
                }
            }
        }
    }

}