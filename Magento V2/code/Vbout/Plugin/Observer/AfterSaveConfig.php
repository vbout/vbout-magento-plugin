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
    const CUSTOM_OPTION_STRING_PATH = 'vbout/api_settings/domain';

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\App\Config\ValueFactory $configValueFactory,
        \Vbout\Plugin\Helper\Data $helper,
        array $data = []
    ) {
        $this->_configValueFactory = $configValueFactory;


        //Helper
        $this->helper = $helper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $authTokens = $this->helper->getAuthTokens();
        $helper = $this->helper;
        $vboutApp = new EcommerceWS($authTokens);


        if($helper->getDomain() == '0') {
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

        $settingsPayload = array(
            'domain'  => $domain,
            'apiname' => 'Magento',
            'apikey'  => $authTokens,
        );
        try{
            $vboutApp->sendAPIIntegrationCreation($settingsPayload,1);
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
            $vboutApp->sendSettingsSync($settings);
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