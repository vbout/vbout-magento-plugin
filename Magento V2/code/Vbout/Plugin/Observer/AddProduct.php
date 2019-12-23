<?php
namespace Vbout\Plugin\Observer;

use Vbout\Plugin\Vbout\services\EcommerceWS;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class AddProduct implements ObserverInterface
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
        $product_id = $observer->getEvent()->getProduct()->getId();
        $product = $this->_productRepository->getById($product_id);
        $this->logger->debug(json_encode($product));
        if($helper->getProductFeed() == 1) {
            //Get variations
            $variation = array();
            foreach ($product->getOptions() as $key => $o) {
                $optionType = $o->getType();
                if ($optionType == 'drop_down') {
                    $values = $o->getValues();
                    $countVariations = 0;
                    $variations = '';
                    foreach ($values as $key => $v) {
                        $variations .= $v->getTitle();
                        if ($countVariations < count($values) - 1)
                            $variations .= ', ';
                        $countVariations++;
                    }
                    $variation[$o->getTitle()] = $variations;
                }
            }
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

            $productData = array(
                "productid" => $product->getId(),
                "name" => $product->getName(),
                "price" => (float)$product->getPrice(),
                "description" => $product->getShortDescription(),
                "discountprice" => $discountPrice,
                "currency"      => $this->storeManager->getStore()->getCurrentCurrency()->getCode(),
                "sku" => $product->getSku(),
                "categoryid" => $categoryId,
                "category" => $categoryName,
                "link" => $product->getProductUrl(),
                "variation" => $variation,
                "image"         => $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage(),
                'domain' => $helper->getDomain(),
            );
            try{
                $result = $vboutApp->Product($productData, 1);
            }
            catch (\Exception $e)
            {}
        }
    }

}