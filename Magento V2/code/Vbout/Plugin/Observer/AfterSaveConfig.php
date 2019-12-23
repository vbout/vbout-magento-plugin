<?php
namespace Vbout\Plugin\Observer;

use Vbout\Plugin\Vbout\services\EcommerceWS;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class AfterSaveConfig implements ObserverInterface
{
    /**
     * @param \Psr\Log\LoggerInterface $_logger
     */
    protected $helper;
    protected $_configValueFactory;

    public function __construct(
        \Magento\Framework\Model\Context $context,

//        \Magento\Framework\Registry $registry,
//        \Magento\Framework\App\Config\ScopeConfigInterface $config,
//        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Config\ValueFactory $configValueFactory,
//        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
//        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        // Helper
        \Vbout\Plugin\Helper\Data $helper,
        array $data = []
    ) {
        $this->_configValueFactory = $configValueFactory;


        //Helper
        $this->helper = $helper;

//        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $authTokens = $this->helper->getAuthTokens();
        $helper = $this->helper;
        $vboutApp = new EcommerceWS($authTokens);


            if($helper->getDomain() == '0')
//        try {
        {
            $domain = $vboutApp->getDomain(array(
                'domain' => $_SERVER['HTTP_HOST'],
                'api_key' => $authTokens
            ));

            $this->_configValueFactory->create()->load(
                self::CUSTOM_OPTION_STRING_PATH,
                'path'
            )->setValue(
                $domain
            )->setPath(
                self::CUSTOM_OPTION_STRING_PATH
            )->save();
        }
            else $domain = $helper->getDomain();

//
//
//        } catch (\Exception $e) {
//            throw new \Exception(__('We can\'t save new option.'));
//        }
        $settingsPayload = array(
            'domain'  => $domain,
            'apiname' => 'Magento',
            'apikey'  => $authTokens,
        );
        try{
            $result = $vboutApp->sendAPIIntegrationCreation($settingsPayload,1);
        }
        catch (\Exception $e)
        {}

        //Sync Settings
        $settings = array(
            'abandoned_carts'       => $helper->getAbandonedCart(),
            'sync_current_products' => $helper->getSyncCurrentProducts(),
            'search'                => $helper->getSearch(),
            'product_visits'        => $helper->getProductVisits(),
            'category_visits'       => $helper->getCategoryVisits(),
            'customers'             => $helper->getCustomers(),
            'product_feed'          => $helper->getProductFeed(),
            'current_customers'     => $helper->getCurrentCustomers(),
            'marketing'             => '0',
            'domain'                => $domain,
            'apiName'               => 'Magento',
        );
        try{
            $result = $vboutApp->sendSettingsSync($settings);
        }
        catch (\Exception $e)
        {}

        //Sync Functionality
        if($helper->getSyncCurrentProducts() == 1)
            $helper->syncCurrentProducts($authTokens,$domain);
        if($helper->getCurrentCustomers() == 1)
            $helper->syncCurrentCustomers($authTokens,$domain);
    }

}