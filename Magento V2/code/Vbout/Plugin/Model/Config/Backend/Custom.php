<?php

namespace Vbout\Plugin\Model\Config\Backend;
use Vbout\Plugin\Vbout\services\EcommerceWS;


class Custom extends \Magento\Framework\App\Config\Value
{
//    const CUSTOM_OPTION_STRING_PATH = 'vbout/api_settings/domain';
//    protected $_configValueFactory;
//    protected $helper;
//
//
//    public function __construct(
//        \Magento\Framework\Model\Context $context,
//
//        \Magento\Framework\Registry $registry,
//        \Magento\Framework\App\Config\ScopeConfigInterface $config,
//        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
//        \Magento\Framework\App\Config\ValueFactory $configValueFactory,
//        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
//        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
//
//        // Helper
//        \Vbout\Plugin\Helper\Data $helper,
//        array $data = []
//    ) {
//        $this->_configValueFactory = $configValueFactory;
//
//
//        //Helper
//        $this->helper = $helper;
//
//
//        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
//    }
//
//
//
//    public function afterSave()
//    {
//
//        $authTokens = $this->helper->getAuthTokens();
//        $helper = $this->helper;
//        $vboutApp = new EcommerceWS($authTokens);
//
//        $domain = $vboutApp->getDomain(array(
//            'domain' => $_SERVER['HTTP_HOST'],
//            'api_key' => $authTokens
//        ));
//        try {
//            $this->_configValueFactory->create()->load(
//                self::CUSTOM_OPTION_STRING_PATH,
//                'path'
//            )->setValue(
//                $domain
//            )->setPath(
//                self::CUSTOM_OPTION_STRING_PATH
//            )->save();
//
//
//        } catch (\Exception $e) {
//            throw new \Exception(__('We can\'t save new option.'));
//        }
//        $settingsPayload = array(
//            'domain'  => $domain,
//            'apiname' => 'Magento',
//            'apikey'  => $authTokens,
//        );
//        $result = $vboutApp->sendAPIIntegrationCreation($settingsPayload,1);
//
//        //Sync Settings
//        $settings = array(
//            'abandoned_carts'       => $helper->getAbandonedCart(),
//            'sync_current_products' => $helper->getSyncCurrentProducts(),
//            'search'                => $helper->getSearch(),
//            'product_visits'        => $helper->getProductVisits(),
//            'category_visits'       => $helper->getCategoryVisits(),
//            'customers'             => $helper->getCustomers(),
//            'product_feed'          => $helper->getProductFeed(),
//            'current_customers'     => $helper->getCurrentCustomers(),
//            'marketing'             => '0',
//            'domain'                => $domain,
//            'apiName'               => 'Magento',
//        );
//        $result = $vboutApp->sendSettingsSync($settings);
//
//        //Sync Functionality
//        if($helper->getSyncCurrentProducts() == 1)
//            $helper->syncCurrentProducts($authTokens,$domain);
//        if($helper->getCurrentCustomers() == 1)
//            $helper->syncCurrentCustomers($authTokens,$domain);
//
//        return parent::afterSave();
//    }
}

