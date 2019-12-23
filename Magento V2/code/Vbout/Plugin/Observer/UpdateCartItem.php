<?php
//
namespace Vbout\Plugin\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Vbout\Plugin\Vbout\services\EcommerceWS;

class UpdateCartItem implements ObserverInterface
{
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
        $action = 2;
        $event = $observer->getEvent();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->create('Magento\Customer\Model\Session');
        if (! $customerSession->isLoggedIn())
            $email = '';
        else
        {
            $customer = $customerSession->getCustomer();

            $email = $customerSession->getCustomer()->getEmail();

        }

        $helper = $this->helper;
        $authTokens = $helper->getAuthTokens();
        if (is_array($authTokens)) {
            if ($helper->getAbandonedCart() == 1) {
//                $this->logger->debug('here message2');
                $vboutApp = new EcommerceWS($authTokens);

                $cartD = $this->_checkoutSession->getQuote();

                if ($cartD->getItemsQty() > 0) {

                    $store = array(
                        "domain" => $helper->getDomain(),
                        "cartcurrency"  => $this->storeManager->getStore()->getCurrentCurrency()->getCode(),
                        "cartid"        => $cartD->getId(),
                        'ipaddress'     => $_SERVER['HTTP_X_FORWARDED_FOR'],
                        "customer"      => $email,
                        "storename"     => $this->storeManager->getStore()->getFrontendName(),
                        "uniqueid"      => $helper->userSessionId(),

//                            "abandonurl"    => "https://johnny.gloclick.com/cart/"
                    );
                    try{
                        $result = $vboutApp->Cart($store, 3);
                        $result = $vboutApp->Cart($store, 1);

                    }
                    catch (\Exception $e)
                    {

                    }
                    $products = $cartD->getAllItems();
                    foreach ($products as $productQ) {

                        $product = $productQ->getProduct();
                        $priceVariations = 0;
                        $options =  $productQ->getProduct()->getTypeInstance(true)->getOrderOptions($productQ->getProduct());

                        //selected variations
//                            $options = $product->getOrderOptions();
                        $priceVariations = 0;

                        //selected variations
                        $variations = array();

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

                        if($product->getSpecialPrice() != 0 )
                            $discountPrice = $product->getSpecialPrice();
                        else $discountPrice = '0.0';
                        if ($product->getCategoryIds()[0] != '') {
                            $categoryId = $product->getCategoryIds()[0];
                            $categoryName = $objectManager->create('Magento\Catalog\Model\Category')->load($product->getCategoryIds()[0])->getName();
                        } else {
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
                            "discountprice" => $discountPrice,
                            "currency"      => $this->storeManager->getStore()->getCurrentCurrency()->getCode(),
                            "sku"           => $product->getSku(),
                            "quantity"      => $productQ->getQty(),
                            "categoryid"    => $categoryId,
                            "category"      => $categoryName,
                            "link"          => $product->getProductUrl(),
                            "variation"     => $variations,
                            "image"         => $productImage,
                            'domain'        => $helper->getDomain(),
                            "uniqueid"      => $helper->userSessionId(),
                        );
                        try{
                            $result = $vboutApp->CartItem($productData, 1);
                        }
                        catch (\Exception $e)
                        {
                        }

                    }
                }
            }
        }
    }
}