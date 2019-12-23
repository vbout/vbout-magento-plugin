<?php

/* * ****************************************************
 * Package   : Vbout
 * Author    : MMG
 * Copyright : (c) 2019
 * ***************************************************** */
?>
<?php

class Vbout_Vbout_Helper_Data extends Mage_Core_Helper_Abstract {

    const XML_PATH_GENERAL = 'vbout/general_settings/';
    const XML_PATH_API = 'vbout/api_settings/';
    const XML_PATH_EM = 'vbout/em_settings/';
    const XML_PATH_TRACKING = 'vbout/tracking_settings/';

//    public function getGeneralSettings($code, $storeId = null) {
//        if (!$storeId) {
//            $storeId = Mage::app()->getStore()->getId();
//        }
//        return Mage::getStoreConfig(self::XML_PATH_GENERAL . $code, $storeId);
//    }

    public function getApiSettings($code, $storeId = null) {
        if (!$storeId) {
            $storeId = Mage::app()->getStore()->getId();
        }
        return Mage::getStoreConfig(self::XML_PATH_API . $code, $storeId);
    }

//    public function getEmSettings($code, $storeId = null) {
//        if (!$storeId) {
//            $storeId = Mage::app()->getStore()->getId();
//        }
//        return Mage::getStoreConfig(self::XML_PATH_EM . $code, $storeId);
//    }

//    public function getTrackingSettings($code, $storeId = null) {
//        if (!$storeId) {
//            $storeId = Mage::app()->getStore()->getId();
//        }
//        return Mage::getStoreConfig(self::XML_PATH_TRACKING . $code, $storeId);
//    }

    public function getAuthTokens() {
        $authTokens = null;
        if ($this->getApiSettings('user_key')) {
            $authTokens = array('api_key' => $this->getApiSettings('user_key'));
        }
//        else {
//            if ($this->getApiSettings('app_key') && $this->getApiSettings('client_secret') && $this->getApiSettings('oauth_token')) {
//                $authTokens = array(
//                    'app_key' => $this->getApiSettings('app_key'),
//                    'client_secret' => $this->getApiSettings('client_secret'),
//                    'oauth_token' => $this->getApiSettings('oauth_token')
//                );
//            }
//        }
        return $authTokens;
    }
    //Get Domain Name
    public function getDomain()
    {
        $domain = null;
        if ($this->getApiSettings('domain')) {
            $domain = $this->getApiSettings('domain');
        }
        return $domain;
    }

    /** API Settings */
    public function getAbandonedCart()
    {
        $abandonedCart = null;
        if ($this->getApiSettings('abandoned_carts')) {
            $abandonedCart = $this->getApiSettings('abandoned_carts');
        }
        return $abandonedCart;
    }

    public function getSearch()
    {
        $search = null;
        if ($this->getApiSettings('search')) {
            $search = $this->getApiSettings('search');
        }
        return $search;
    }

    public function getProductVisits()
    {
        $product_visits = null;
        if ($this->getApiSettings('product_visits')) {
            $product_visits = $this->getApiSettings('product_visits');
        }
        return $product_visits;
    }

    public function getCategoryVisits()
    {
        $category_visits = null;
        if ($this->getApiSettings('category_visits')) {
            $category_visits = $this->getApiSettings('category_visits');
        }
        return $category_visits;
    }

    public function getCustomers()
    {
        $customers = null;
        if ($this->getApiSettings('customers')) {
            $customers = $this->getApiSettings('customers');
        }
        return $customers;
    }

    public function getCurrentCustomers()
    {
        $current_customers = null;
        if ($this->getApiSettings('current_customers')) {
            $current_customers = $this->getApiSettings('current_customers');
        }
        return $current_customers;
    }

    public function getProductFeed()
    {
        $product_feed = null;
        if ($this->getApiSettings('product_feed')) {
            $product_feed = $this->getApiSettings('product_feed');
        }
        return $product_feed;
    }

    public function getSyncCurrentProducts()
    {
        $sync_current_products = null;
        if ($this->getApiSettings('sync_current_products')) {
            $sync_current_products = $this->getApiSettings('sync_current_products');
        }
        return $sync_current_products;
    }

    public function userSessionId()
    {
        $sessionId = $cookieValue = Mage::getModel('core/cookie')->get('vbtEcommerceUniqueId');
        return $sessionId;
    }

    }
