<?php

/* * ****************************************************
 * Package   : Vbout
 * Author    : MMG
 * Copyright : (c) 2019
 * ***************************************************** */
?>
<?php


require_once Mage::getBaseDir('lib') . '/Vbout/services/EmailMarketingWS.php';
require_once Mage::getBaseDir('lib') . '/Vbout/services/EcommerceWS.php';

class Vbout_Vbout_Model_Adminhtml_Observer {

    public function customSystemConfig(Varien_Event_Observer $observer) {
        $config = $observer->getConfig();
        $response = array();
        try {
            $helper = Mage::helper('vbout');
            $authTokens = $helper->getAuthTokens();
             if (is_array($authTokens)) {
                $em = new EmailMarketingWS($authTokens);
                $lists = $em->getMyLists();
                 $setup = new Mage_Core_Model_Config();
                 $vboutApp = new EcommerceWS($authTokens);

                 //Get Domain
                 $url = $_SERVER['HTTP_HOST'];

                 $domain = $vboutApp->getDomain(array(
                         'domain' => parse_url($url)['path'],
                         'api_key' => $authTokens
                     ));

    //                     Create API Integrations
                     $settingsPayload = array(
                         'domain'  => $domain,
                         'apiname' => 'Magento',
                         'apikey'  => $authTokens,
                     );
                     $result = $vboutApp->sendAPIIntegrationCreation($settingsPayload,1);
                     $setup->saveConfig('vbout/api_settings/domain', $domain, 'default', 0);

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
                     $result = $vboutApp->sendSettingsSync($settings);


                     //Sync Functionality
                  if($helper->getSyncCurrentProducts() == 1)
                        $this->syncCurrentProducts($authTokens,$domain);
                 if($helper->getCurrentCustomers() == 1)
                     $this->syncCurrentCustomers($authTokens,$domain);
            }
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, 'vbout-config.log');
        }

        if(!isset($domain['errorCode'])) {

            $xmlString = '<application_settings type="group" translate="label">
                            <label>Integration Settings</label>
                            <frontend_type>text</frontend_type>
                            <sort_order>20</sort_order>
                            <show_in_default>1</show_in_default>
                            <show_in_website>1</show_in_website>
                            <show_in_store>1</show_in_store>
                            <expanded>1</expanded>
                            <fields>
                                <abandoned_carts translate="label">
                                    <config_path>vbout/api_settings/abandoned_carts</config_path>
                                    <label>Abandoned carts</label>
                                    <source_model>adminhtml/system_config_source_yesno</source_model>
                                    <frontend_type>select</frontend_type>
                                    <sort_order>20</sort_order>
                                    <show_in_default>1</show_in_default>
                                    <show_in_website>1</show_in_website>
                                    <show_in_store>1</show_in_store>
                                    <comment>When a checkout/order is created or updated on Magento.</comment>
                                </abandoned_carts>
                                <search translate="label">
                                    <config_path>vbout/api_settings/search</config_path>
                                    <label>Product Search</label>
                                    <source_model>adminhtml/system_config_source_yesno</source_model>
                                    <frontend_type>select</frontend_type>
                                    <sort_order>20</sort_order>
                                    <show_in_default>1</show_in_default>
                                    <show_in_website>1</show_in_website>
                                    <show_in_store>1</show_in_store>
                                    <comment>When customers search for a specific product on Magento.</comment>
                                </search>
                                <product_visits translate="label">
                                    <config_path>vbout/api_settings/product_visits</config_path>
                                    <label>Product Visits</label>
                                    <source_model>adminhtml/system_config_source_yesno</source_model>
                                    <frontend_type>select</frontend_type>
                                    <sort_order>20</sort_order>
                                    <show_in_default>1</show_in_default>
                                    <show_in_website>1</show_in_website>
                                    <show_in_store>1</show_in_store>
                                    <comment>When customers visit a product on Magento.</comment>
                                </product_visits>
                                <category_visits translate="label">
                                    <config_path>vbout/api_settings/category_visits</config_path>
                                    <label>Category Visits</label>
                                    <source_model>adminhtml/system_config_source_yesno</source_model>
                                    <frontend_type>select</frontend_type>
                                    <sort_order>20</sort_order>
                                    <show_in_default>1</show_in_default>
                                    <show_in_website>1</show_in_website>
                                    <show_in_store>1</show_in_store>
                                    <comment>When customers\' visit a specific category on Magento</comment>
                                </category_visits>
                                <customers translate="label">
                                    <config_path>vbout/api_settings/customers</config_path>
                                    <label>Customer data</label>
                                    <frontend_type>select</frontend_type>
                                    <source_model>adminhtml/system_config_source_yesno</source_model>
                                    <sort_order>20</sort_order>
                                    <show_in_default>1</show_in_default>
                                    <show_in_website>1</show_in_website>
                                    <show_in_store>1</show_in_store>
                                    <comment>When customers\' profiles are added or updated on Magento.</comment>
                                </customers>
                                <current_customers translate="label">
                                    <config_path>vbout/api_settings/current_customers</config_path>
                                    <label>Exsisting Customers</label>
                                    <frontend_type>select</frontend_type>
                                    <source_model>adminhtml/system_config_source_yesno</source_model>
                                    <sort_order>20</sort_order>
                                    <show_in_default>1</show_in_default>
                                    <show_in_website>1</show_in_website>
                                    <show_in_store>1</show_in_store>
                                    <comment>Syncs customers\' data before installing the plugin on Magento.</comment>
                                </current_customers>
                                <product_feed translate="label">
                                    <config_path>vbout/api_settings/product_feed</config_path>
                                    <label>Product data</label>
                                    <frontend_type>select</frontend_type>
                                    <source_model>adminhtml/system_config_source_yesno</source_model>
                                    <sort_order>20</sort_order>
                                    <show_in_default>1</show_in_default>
                                    <show_in_website>1</show_in_website>
                                    <show_in_store>1</show_in_store>
                                    <comment>When products are added or updated on Magento.</comment>
                                </product_feed>
                                <sync_current_products translate="label">
                                    <config_path>vbout/api_settings/sync_current_products</config_path>
                                    <label>Existing products</label>
                                    <frontend_type>select</frontend_type>
                                    <source_model>adminhtml/system_config_source_yesno</source_model>
                                    <sort_order>20</sort_order>
                                    <show_in_default>1</show_in_default>
                                    <show_in_website>1</show_in_website>
                                    <show_in_store>1</show_in_store>
                                    <comment>Syncs products data before installing the plugin on Magento.</comment>
                                </sync_current_products>
                            </fields>
                        </application_settings>';
        }
        else $xmlString =
                '<vbout_api_error translate="label" module="vbout">
                     <label>Magento could not connect to VBOUT through integration. Please check the API Key (VBOUT APP -> Settings -> API Integrations Named as USER KEY) </label>
                     <sort_order>60</sort_order>
                    <frontend_type>label</frontend_type>
                    <show_in_default>1</show_in_default>
                    <show_in_website>1</show_in_website>
                    <show_in_store>1</show_in_store>
                </vbout_api_error>';
            $vboutSectionGroups = $config->getNode('sections/vbout/groups');
            $newGroupXml = new Mage_Core_Model_Config_Element($xmlString);
            $vboutSectionGroups->appendChild($newGroupXml);
          return $this;
    }
    //Sync All Customers
    public function syncCurrentCustomers($authTokens,$domain)
    {
        $vboutApp = new EcommerceWS($authTokens);
        $users = Mage::getModel('customer/customer')->getCollection()
            ->addAttributeToSelect('firstname')
            ->addAttributeToSelect('lastname')
            ->addAttributeToSelect('email');
        if (count($users) > 0) {
            foreach ($users as $user) {

                $customer = array(
                    "firstname" => $user->getFirstname(),
                    "lastname"  => $user->getLastname(),
                    "email"     => $user->getEmail(),
                    'domain'    => $domain,
                    'ipaddress' => $_SERVER['REMOTE_ADDR']
                );
                $result = $vboutApp->Customer($customer, 1);
            }
        }
    }

    //Sync All Products
    private function syncCurrentProducts($authTokens,$domain)
    {
        $vboutApp = new EcommerceWS($authTokens);
        $products = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('*'); // select all attributes
//            ->setPageSize(5000) // limit number of results returned
//            ->setCurPage(1); // set the offset (useful for pagination)


        foreach ($products as $product)
        {
            //Get variations
            $variation = array();
            foreach($product->getOptions() as $key=>$o) {
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
            if ($product->getPrice() != $product->getFinalPrice())
                $discountPrice = $product->getFinalPrice();
                    else $discountPrice = '0.0';
            if($product->getCategoryIds()[0] != '')
                $categoryName = Mage::getModel('catalog/category')->load($product->getCategoryIds()[0])->getName();
                else $categoryName = 'N/A';
            $productData = array(
                "productid"     => $product->getId(),
                "name"          => $product->getName(),
                "price"         => (float)$product->getPrice(),
                "description"   => $product->getDescription(),
                "discountprice" =>  $discountPrice,
                "currency"      => Mage::app()->getStore()->getCurrentCurrencyCode(),
                "sku"           => $product->getSku(),
                "categoryid"    => $product->getCategoryIds()[0],
                "category"      => $categoryName,
                "variation"     => $variation,
                "link"          => $product->getProductUrl(),
                "image"         => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'catalog/product'.$product->getImage(),
                'domain'        => $domain,
            );
            $result = $vboutApp->Product($productData,1);
        }
    }
}
