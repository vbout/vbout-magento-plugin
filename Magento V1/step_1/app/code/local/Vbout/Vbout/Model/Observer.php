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

class Vbout_Vbout_Model_Observer {

    public function customerRegisterSuccess(Varien_Event_Observer $observer) {
        try {
            $event = $observer->getEvent();
            $customer = $event->getCustomer();
            $email = $customer->getEmail();
            if ($email) {
                $storeId = Mage::app()->getStore()->getId();
                $helper = Mage::helper('vbout');
                $authTokens = $helper->getAuthTokens();
                if(is_array($authTokens)) {
                    if ($helper->getCustomers() == 1) {

                        $customerData = array(
                            "firstname" => $customer->getFirstname(),
                            "lastname"  => $customer->getLastname(),
                            "email"     => $customer->getEmail(),
                            'domain'    => $helper->getDomain(),
                            'ipaddress' => $_SERVER['REMOTE_ADDR'],
                            "uniqueid"  => $helper->userSessionId(),

                        );
                        $vboutApp = new EcommerceWS($authTokens);
                        $result = $vboutApp->Customer($customerData, 1);
                    }
                }
            }
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, 'vbout-ecommerce-marketing.log');
        }
    }
    // Add to cart checkout Create
    public function cartCheckoutCreate(Varien_Event_Observer $observer)
    {
        $action = 1;
        try {
            $event = $observer->getEvent();
            if( ! Mage::getSingleton('customer/session')->isLoggedIn())
            {
                $customer = $event->getCustomer();
                if(empty((array) $customer))
                    $email = '';
                else  $email = $customer->getEmail();

            }
            else
                $email = Mage::getSingleton('customer/session')->getCustomer()->getEmail();

            $storeId = Mage::app()->getStore()->getId();
            $helper = Mage::helper('vbout');
            $authTokens = $helper->getAuthTokens();
            if (is_array($authTokens)) {
                if ($helper->getAbandonedCart() == 1) {
                    $vboutApp = new EcommerceWS($authTokens);
                    $cart = Mage::getModel('checkout/session')->getQuote();
                    $cartD = Mage::getSingleton('checkout/cart')->getQuote();

                    if ($cartD->getItemsQty() > 0) {

                        $store = array(
                            "domain" => $helper->getDomain(),
                            "cartcurrency"  => Mage::app()->getStore()->getCurrentCurrencyCode(),
                            "cartid"        => $cart->getId(),
                            'ipaddress'     => $_SERVER['REMOTE_ADDR'],
                            "customer"      => $email,
                            "storename"     => Mage::app()->getStore()->getFrontendName(),
                            "uniqueid"      => $helper->userSessionId(),

//                            "abandonurl"    => "https://johnny.gloclick.com/cart/"
                        );
                        $result = $vboutApp->Cart($store, $action);
                        $products = $cartD->getAllItems();
                        foreach ($products as $productQ) {
                            $product = $productQ->getProduct();

                            $options = $product->getTypeInstance(true)->getOrderOptions($product);
                            $priceVariations = 0;

                            //selected variations
                            $variations = array();
                            if (!empty($options)) {
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
                                $options = Mage::getModel('catalog/product_option')->getProductOptionCollection($product);
                                foreach ($options as $option) {
                                    if ($option->getType() === 'drop_down') {
                                        $values = Mage::getSingleton('catalog/product_option_value')->getValuesCollection($option);
                                        foreach ($values as $value) {
                                            if (isset($variations[$option->getDefaultTitle()])) {
                                                if ($variations[$option->getDefaultTitle()] == $value->getTitle()) {
                                                    $priceVariations = $priceVariations + (double)$value->getPrice();
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            if ($product->getPrice() != $product->getFinalPrice()) {
//                                $discountPrice = $product->getPrice() - ($product->getFinalPrice() - $priceVariations);
                                $discountPrice = ($product->getFinalPrice() - $priceVariations);

                            } else $discountPrice = '0.0';
                            if ($product->getCategoryIds()[0] != '') {
                                $categoryId = $product->getCategoryIds()[0];
                                $categoryName = Mage::getModel('catalog/category')->load($product->getCategoryIds()[0])->getName();
                            } else {
                                $categoryName = 'N/A';
                                $categoryId = 'N/A';
                            }
                            //Image
                            $productImage = Mage::getModel('catalog/product')->load($product->getId());
                            $productImage = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $productImage->getImage();

                            $productData = array(
                                "productid"     => $product->getId(),
                                "cartid"        => $cart->getId(),
                                "name"          => $product->getName(),
                                "price"         => (float)$product->getFinalPrice(),
                                "description"   => $product->getDescription(),
                                "discountprice" => $discountPrice,
                                "currency"      => Mage::app()->getStore()->getCurrentCurrencyCode(),
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
                            $result = $vboutApp->CartItem($productData, 1);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, 'vbout-ecommerce-marketing.log');
        }
    }

    public function checkoutCartOrderCreate(Varien_Event_Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        if ($quote->getData('checkout_method') != Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER) {
            return;
        }
        $customer = $quote->getCustomer();
        $action = 1;
        try {
            $event = $observer->getEvent();
            if( ! Mage::getSingleton('customer/session')->isLoggedIn())
            {
                $email = $customer->getEmail();
            }
            else
                $email = Mage::getSingleton('customer/session')->getCustomer()->getEmail();

            $storeId = Mage::app()->getStore()->getId();
            $helper = Mage::helper('vbout');
            $authTokens = $helper->getAuthTokens();
            if (is_array($authTokens)) {
                if ($helper->getAbandonedCart() == 1) {
                    $vboutApp = new EcommerceWS($authTokens);
                    $cart = Mage::getModel('checkout/session')->getQuote();

                    $customerData = array(
                        "firstname"     => $customer->getFirstname(),
                        "lastname"      => $customer->getLastname(),
                        "email"         => $customer->getEmail(),
                        'domain'        => $helper->getDomain(),
                        'ipaddress'     => $_SERVER['REMOTE_ADDR'],
                        "uniqueid"      => $helper->userSessionId(),
                    );
                    $result = $vboutApp->Customer($customerData, 1);
                    $cartD = Mage::getSingleton('checkout/cart')->getQuote();

                    if ($cartD->getItemsQty() > 0) {
                        $store = array(
                            "domain" => $helper->getDomain(),
                            "cartcurrency"  => Mage::app()->getStore()->getCurrentCurrencyCode(),
                            "cartid"        => $cart->getId(),
                            'ipaddress'     => $_SERVER['REMOTE_ADDR'],
                            "customer"      => $email,
                            "uniqueid"      => $helper->userSessionId(),
                            "storename"     => Mage::app()->getStore()->getFrontendName(),
//                            "abandonurl" => "https://johnny.gloclick.com/cart/"
                        );
                        $result = $vboutApp->Cart($store, $action);
                        $products = $cartD->getAllItems();

                        foreach ($products as $productQ) {
                            $product = $productQ->getProduct();

                            $options = $product->getTypeInstance(true)->getOrderOptions($product);
                            $priceVariations = 0;

                            //selected variations
                            $variations = array();
                            if (!empty($options)) {
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
                                $options = Mage::getModel('catalog/product_option')->getProductOptionCollection($product);
                                foreach ($options as $option) {
                                    if ($option->getType() === 'drop_down') {
                                        $values = Mage::getSingleton('catalog/product_option_value')->getValuesCollection($option);
                                        foreach ($values as $value) {
                                            if (isset($variations[$option->getDefaultTitle()])) {
                                                if ($variations[$option->getDefaultTitle()] == $value->getTitle()) {
                                                    $priceVariations = $priceVariations + (double)$value->getPrice();
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            if ($product->getPrice() != $product->getFinalPrice()) {
//                                $dis Price = $product->getPrice() - ($product->getFinalPrice() - $priceVariations);
                                $discountPrice = ($product->getFinalPrice() - $priceVariations);

                            } else $discountPrice = '0.0';
                            if ($product->getCategoryIds()[0] != '') {
                                $categoryId = $product->getCategoryIds()[0];
                                $categoryName = Mage::getModel('catalog/category')->load($product->getCategoryIds()[0])->getName();
                            } else {
                                $categoryName = 'N/A';
                                $categoryId = 'N/A';
                            }
                            //Image
                            $productImage = Mage::getModel('catalog/product')->load($product->getId());
                            $productImage = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $productImage->getImage();

                            $productData = array(
                                "productid"     => $product->getId(),
                                "cartid"        => $cart->getId(),
                                "name"          => $product->getName(),
                                "price"         => (float)$product->getFinalPrice(),
                                "description"   => $product->getDescription(),
                                "discountprice" => $discountPrice,
                                "currency"      => Mage::app()->getStore()->getCurrentCurrencyCode(),
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
                            $result = $vboutApp->CartItem($productData, 1);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, 'vbout-ecommerce-marketing.log');
        }

    }

    public function cartRemoveItem(Varien_Event_Observer $observer)
    {
        $action = 1;
        try {
            $event = $observer->getEvent();
            if( ! Mage::getSingleton('customer/session')->isLoggedIn())
            {
                $customer = $event->getCustomer();
                if(empty((array) $customer))
                    $email = '';
                else  $email = $customer->getEmail();

            }
            else
                $email = Mage::getSingleton('customer/session')->getCustomer()->getEmail();

            $storeId = Mage::app()->getStore()->getId();
            $helper = Mage::helper('vbout');
            $authTokens = $helper->getAuthTokens();
            if (is_array($authTokens)) {
                if ($helper->getAbandonedCart() == 1) {
                    $vboutApp = new EcommerceWS($authTokens);
                    $cart = Mage::getModel('checkout/session')->getQuote();

                    $clearCartData = array(
                        "domain"        => $helper->getDomain(),
                        "cartid"        => $cart->getId(),
                    );
                    $result = $vboutApp->Cart($clearCartData, 4);

                    $cartD = Mage::getSingleton('checkout/cart')->getQuote();
                    $products = $cartD->getAllItems();
                    if( $cartD->getItemsQty() > 0 )
                    {
                        $store = array(
                            "domain"        => $helper->getDomain(),
                            "cartcurrency"  => Mage::app()->getStore()->getCurrentCurrencyCode(),
                            "cartid"        => $cart->getId(),
                            'ipaddress'     => $_SERVER['REMOTE_ADDR'],
                            "customer"      => $email,
                            "storename"     => Mage::app()->getStore()->getFrontendName(),
                            "uniqueid"      => $helper->userSessionId(),

//                        "abandonurl"    => "https://johnny.gloclick.com/cart/"
                        );
                        $result = $vboutApp->Cart($store, $action);

                        foreach ($products as $productQ)
                        {
                            $product = $productQ->getProduct();

                            $options = $product->getTypeInstance(true)->getOrderOptions($product);
                            $priceVariations = 0;

                            //selected variations
                            $variations = array();
                            if (!empty($options)) {
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
                                $options = Mage::getModel('catalog/product_option')->getProductOptionCollection($product);
                                foreach ($options as $option) {
                                    if ($option->getType() === 'drop_down') {
                                        $values = Mage::getSingleton('catalog/product_option_value')->getValuesCollection($option);
                                        foreach ($values as $value) {
                                            if (isset($variations[$option->getDefaultTitle()])) {
                                                if ($variations[$option->getDefaultTitle()] == $value->getTitle())
                                                {
                                                    $priceVariations = $priceVariations + (double)$value->getPrice();
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            if ($product->getPrice() != $product->getFinalPrice())
                            {
//                                $discountPrice = $product->getPrice() - ($product->getFinalPrice() - $priceVariations);
                                $discountPrice =($product->getFinalPrice() - $priceVariations);

                            }
                            else $discountPrice = '0.0';
                            if ($product->getCategoryIds()[0] != '')
                            {
                                $categoryId = $product->getCategoryIds()[0];
                                $categoryName = Mage::getModel('catalog/category')->load($product->getCategoryIds()[0])->getName();
                            }
                            else
                            {
                                $categoryName = 'N/A';
                                $categoryId = 'N/A';
                            }
                            //Image
                            $productImage = Mage::getModel('catalog/product')->load($product->getId());
                            $productImage = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'catalog/product'.$productImage->getImage();

                            $productData = array(
                                "productid"     => $product->getId(),
                                "cartid"        => $cart->getId(),
                                "name"          => $product->getName(),
                                "price"         => (float)$product->getFinalPrice(),
                                "description"   => $product->getDescription(),
                                "discountprice" =>  $discountPrice,
                                "currency"      => Mage::app()->getStore()->getCurrentCurrencyCode(),
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
                    else{
                        $clearCartData = array(
                            "domain"        => $helper->getDomain(),
                            "cartid"        => $cart->getId(),
                        );
                        $result = $vboutApp->Cart($clearCartData, 3);

                    }

                }
            }
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, 'vbout-ecommerce-marketing.log');
        }
    }

    // Cart Update
    public function cartProductUpdateAfter(Varien_Event_Observer $observer)
    {
        $action = 2;
        try {
            $event = $observer->getEvent();
            if( ! Mage::getSingleton('customer/session')->isLoggedIn()) {
                $customer = $event->getCustomer();
                if (empty((array)$customer))
                    $email = '';
                else  $email = $customer->getEmail();
            }

            else
                $email = Mage::getSingleton('customer/session')->getCustomer()->getEmail();

            $storeId = Mage::app()->getStore()->getId();
            $helper = Mage::helper('vbout');
            $authTokens = $helper->getAuthTokens();
            if (is_array($authTokens)) {
                if ($helper->getAbandonedCart() == 1) {
                    $vboutApp = new EcommerceWS($authTokens);
                    $cart = Mage::getModel('checkout/session')->getQuote();
                    if ($cart->getItemsQty() > 0) {
                        $store = array(
                            "domain"        => $helper->getDomain(),
                            "cartcurrency"  => Mage::app()->getStore()->getCurrentCurrencyCode(),
                            "cartid"        => $cart->getId(),
                            'ipaddress'     => $_SERVER['REMOTE_ADDR'],
                            "customer"      => $email,
                            "storename"     => Mage::app()->getStore()->getFrontendName(),
                            "uniqueid"      => $helper->userSessionId(),

//                            "abandonurl" => "https://johnny.gloclick.com/cart/"
                        );
                        $result = $vboutApp->Cart($store, 2 );
                        $cartD = Mage::getSingleton('checkout/session')->getQuote();
                        $products = $cartD->getAllVisibleItems();
                        foreach ($products as $productQ) {
                            $product = $productQ->getProduct();

                            $options = $product->getTypeInstance(true)->getOrderOptions($product);

                            //selected variations
                            $priceVariations = 0;
                            $variations = array();
                            if (!empty($options)) {
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
                                $options = Mage::getModel('catalog/product_option')->getProductOptionCollection($product);
                                foreach ($options as $option) {
                                    if ($option->getType() === 'drop_down') {
                                        $values = Mage::getSingleton('catalog/product_option_value')->getValuesCollection($option);
                                        foreach ($values as $value) {
                                            if (isset($variations[$option->getDefaultTitle()])) {
                                                if ($variations[$option->getDefaultTitle()] == $value->getTitle()) {
                                                    $priceVariations = $priceVariations + (double)$value->getPrice();
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            if ($product->getPrice() != $product->getFinalPrice()) {
//                        $discountPrice = $product->getPrice() - ($product->getFinalPrice() - $priceVariations);
                                $discountPrice = ($product->getFinalPrice() - $priceVariations);

                            } else $discountPrice = '0.0';
                            if ($product->getCategoryIds()[0] != '') {
                                $categoryId = $product->getCategoryIds()[0];
                                $categoryName = Mage::getModel('catalog/category')->load($product->getCategoryIds()[0])->getName();
                            } else {
                                $categoryName = 'N/A';
                                $categoryId = 'N/A';
                            }
                            $productImage = Mage::getModel('catalog/product')->load($product->getId());
                            $productImage = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $productImage->getImage();

                            $productData = array(
                                "productid"     => $product->getId(),
                                "cartid"        => $cart->getId(),
                                "name"          => $product->getName(),
                                "price"         => (float)$product->getFinalPrice(),
                                "description"   => $product->getDescription(),
                                "discountprice" => $discountPrice,
                                "currency"      => Mage::app()->getStore()->getCurrentCurrencyCode(),
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
                            $result1 = $vboutApp->CartItem($productData, 3);
                            $result = $vboutApp->CartItem($productData, 1);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, 'vbout-ecommerce-marketing.log');
        }
    }

    //Product Search Query
    public function productSearchQuery(Varien_Event_Observer $observer)
    {
        //only enter if there is query search
        if( isset($_GET['q']))
        {
            $event = $observer->getEvent();
            $helper = Mage::helper('vbout');
            if ($helper->getSearch() == 1) {
                try {
                    if (!Mage::getSingleton('customer/session')->isLoggedIn())
                        $email = '';
                    else
                        $email = Mage::getSingleton('customer/session')->getCustomer()->getEmail();
                    $query  = $_GET['q'];
                    $authTokens = $helper->getAuthTokens();
                    $vboutApp = new EcommerceWS($authTokens);
                    $searchPayload = array(
                        'domain'    => $helper->getDomain(),
                        'customer'  => $email,
                        'query'     => $query,
                        "uniqueid"  => $helper->userSessionId(),
                        'ipaddress' => $_SERVER['REMOTE_ADDR'],
                    );
                    $result = $vboutApp->sendProductSearch($searchPayload);
                } catch (Exception $e) {
                    Mage::log($e->getMessage(), null, 'vbout-ecommerce-marketing.log');
                }
            }
        }
    }

    //Product View
    public function productView(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
        $helper = Mage::helper('vbout');
        $authTokens = $helper->getAuthTokens();
        $vboutApp = new EcommerceWS($authTokens);
        //User Email if logged in
        if( ! Mage::getSingleton('customer/session')->isLoggedIn())
            $email = '';
        else
        {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $email= $customer->getEmail();
        }
        $product_id = $observer->getEvent()->getProduct()->getId();
        $product = Mage::getModel('catalog/product')->load($product_id);
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
            if ($product->getCategoryIds()[0] != '')
            {
                $categoryId = $product->getCategoryIds()[0];
                $categoryName = Mage::getModel('catalog/category')->load($product->getCategoryIds()[0])->getName();
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
                'ipaddress'         => $_SERVER['REMOTE_ADDR'],
                "productid"         => $product->getId(),
                "name"              => $product->getName(),
                "price"             => $price,
                "description"       => $product->getDescription(),
                "discountprice"     => $discountPrice,
                "currency"          => Mage::app()->getStore()->getCurrentCurrencyCode(),
                "sku"               => $product->getSku(),
                "categoryid"        => $categoryId,
                "category"          => $categoryName,
//                "variation"         => $variation,
                "link"              => $product->getProductUrl(),
                "image"             => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage(),
                'domain'            => $helper->getDomain(),
                "uniqueid"          => $helper->userSessionId(),

            );
            $result = $vboutApp->Product($productData, 1);
        }
        if ($helper->getCategoryVisits() == 1)
        {
            if(count($product->getCategoryIds())>0)
            {
                foreach ( $product->getCategoryIds()as $category_id)
                {
                    $category =  Mage::getModel('catalog/category')->load($category_id);
                    $category = array(
                        "customer"      => $email,
                        "domain"        => $helper->getDomain(),
                        "categoryid"    => $category_id,
                        "name"          => $category->getName(),
                        "link"          => $category->getUrl(),
                        'ipaddress'     => $_SERVER['REMOTE_ADDR'],
                        "uniqueid"      => $helper->userSessionId(),

                    );
                    $result = $vboutApp->Category($category, 1);
                }
            }
        }
    }

    //Orders Creation
    public function salesOrderPlaceAfter(Varien_Event_Observer $observer)
    {
        try {
            $event = $observer->getEvent();
            $helper = Mage::helper('vbout');
            $authTokens = $helper->getAuthTokens();
            $vboutApp = new EcommerceWS($authTokens);
            if ($helper->getAbandonedCart() == 1)
            {

                $discountTotal = '0.0';
                $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();

                $order = Mage::getModel('sales/order')->load($orderId);
                if ($order['discount_amount'] != 0 )
                    $discountTotal = $order['discount_amount'];

                $orderData = array(
                    "cartid" => $order['quote_id'],
                    "uniqueid"      => $helper->userSessionId(),
                    "domain" => $helper->getDomain(),
                    'ipaddress'     =>$_SERVER['REMOTE_ADDR'],
                    "orderid" => $order['increment_id'],
//                    "paymentmethod" => $order['shipping_description'],
                    "paymentmethod" => $order->getPayment()->getMethod(),
                    "grandtotal" => $order->getGrandTotal(),
                    "orderdate" => strtotime(date('Y-m-d H:i:s')),
                    "shippingmethod" => $order['shipping_description'],
                    "shippingcost" => $order['shipping_amount'],
                    "subtotal" => $order['subtotal'],
                    "discountvalue" => $discountTotal,
                    "taxcost" => $order['tax_amount'],
//                    "otherfeecost" => $order->get_fees(),
                    "currency" => $order['order_currency_code'],
                    "status" => $observer->getEvent()->getOrder()->getStatus(),
                    "notes" => $order['customer_note'],
                    "storename" =>   $order['store_name'],
                    "customerinfo" => array(
                        "firstname" => $order['customer_firstname'],
                        "lastname" => $order['customer_lastname'],
                        "email" => $order['customer_email'],
                        "phone" => $order->getShippingAddress()->getTelephone(),
                    ),
                    "billinginfo" => array(
                        "firstname" => $order->getBillingAddress()->getFirstname(),
                        "lastname" =>  $order->getBillingAddress()->getLastname(),
                        "email" => $order->getBillingAddress()->getEmail(),
                        "phone" => $order->getBillingAddress()->getTelephone(),
//                        "company" => $order->shipping_company,
                        "address" => $order->getBillingAddress()->getTelephone(),
                        "address2" => $order->getBillingAddress()->getTelephone(),
                        "city" => $order->getBillingAddress()->getCity(),
                        "statename" => $order->getBillingAddress()->getRegionId(),
                        "countryname" => $order->getBillingAddress()->getCountryId(),
                        "zipcode" => $order->getBillingAddress()->getPostcode(),
                    ),
                    "shippinginfo" => array(
                        "firstname" => $order->getShippingAddress()->getFirstname(),
                        "lastname" =>  $order->getShippingAddress()->getLastname(),
                        "email" => $order->getShippingAddress()->getEmail(),
                        "phone" => $order->getShippingAddress()->getTelephone(),
//                        "company" => $order->shipping_company,
                        "address" => $order->getShippingAddress()->getTelephone(),
                        "address2" => $order->getShippingAddress()->getTelephone(),
                        "city" => $order->getShippingAddress()->getCity(),
                        "statename" => $order->getShippingAddress()->getRegionId(),
                        "countryname" => $order->getShippingAddress()->getCountryId(),
                        "zipcode" => $order->getShippingAddress()->getPostcode(),
                    )
                );
                $result = $vboutApp->Order($orderData, 1);
            }
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, 'vbout-ecommerce-marketing.log');
        }
    }

    //Product Add
    public function catalogProductSaveAfter(Varien_Event_Observer $observer)
    {
        $helper = Mage::helper('vbout');
        $authTokens = $helper->getAuthTokens();
        $vboutApp = new EcommerceWS($authTokens);
        $product_id = $observer->getEvent()->getProduct()->getId();
        $product = Mage::getModel('catalog/product')->load($product_id);

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
            if ($product->getCategoryIds()[0] != '')
            {
                $categoryId = $product->getCategoryIds()[0];
                $categoryName = Mage::getModel('catalog/category')->load($product->getCategoryIds()[0])->getName();
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
                "description" => $product->getDescription(),
                "discountprice" => $discountPrice,
                "currency" => Mage::app()->getStore()->getCurrentCurrencyCode(),
                "sku" => $product->getSku(),
                "categoryid" => $categoryId,
                "category" => $categoryName,
                "link" => $product->getProductUrl(),
                "variation" => $variation,
                "image" => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage(),
                'domain' => $helper->getDomain(),
            );
            $result = $vboutApp->Product($productData, 1);
        }
    }

    public function adminUpdateOrder(Varien_Event_Observer $observer)
    {
        try {

            $helper = Mage::helper('vbout');
            $authTokens = $helper->getAuthTokens();
            $vboutApp = new EcommerceWS($authTokens);
            if ($helper->getAbandonedCart() == 1)
            {
                $discountTotal = '0.0';
                $order = $observer->getEvent()->getOrder();
                $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();

                $order = Mage::getModel('sales/order')->load($orderId);
                if ($order['discount_amount'] != 0 )
                    $discountTotal = $order['discount_amount'];
                $order = $observer->getEvent()->getOrder()->getData();
                $orderId = $order['entity_id'];
                $order = Mage::getModel('sales/order')->load($orderId);


                $orderData = array(
                    "cartid" => $order['quote_id'],
                    'ipaddress'     =>$_SERVER['REMOTE_ADDR'],
                    "domain" => $helper->getDomain(),
                    "orderid" => $order['increment_id'],
                    "paymentmethod" => $order->getPayment()->getMethod(),
                    "grandtotal" => $order->getGrandTotal(),
                    "orderdate" => strtotime(date('Y-m-d H:i:s')),
                    "shippingmethod" => $order['shipping_description'],
                    "shippingcost" => $order['shipping_amount'],
                    "subtotal" => $order['subtotal'],
                    "discountvalue" => $discountTotal,
                    "taxcost" => $order['tax_amount'],
//                    "otherfeecost" => $order->get_fees(),
                    "currency" => $order['order_currency_code'],
                    "status" => $order->getStatus(),
                    "notes" => $order['customer_note'],
                    "storename" =>   $order['store_name'],
                    "customerinfo" => array(
                        "firstname" => $order['customer_firstname'],
                        "lastname" => $order['customer_lastname'],
                        "email" => $order['customer_email'],
                        "phone" => $order->getShippingAddress()->getTelephone(),
                    ),
                    "billinginfo" => array(
                        "firstname" => $order->getBillingAddress()->getFirstname(),
                        "lastname" =>  $order->getBillingAddress()->getLastname(),
                        "email" => $order->getBillingAddress()->getEmail(),
                        "phone" => $order->getBillingAddress()->getTelephone(),
//                        "company" => $order->shipping_company,
                        "address" => $order->getBillingAddress()->getTelephone(),
                        "address2" => $order->getBillingAddress()->getTelephone(),
                        "city" => $order->getBillingAddress()->getCity(),
                        "statename" => $order->getBillingAddress()->getRegionId(),
                        "countryname" => $order->getBillingAddress()->getCountryId(),
                        "zipcode" => $order->getBillingAddress()->getPostcode(),
                    ),
                    "shippinginfo" => array(
                        "firstname" => $order->getShippingAddress()->getFirstname(),
                        "lastname" =>  $order->getShippingAddress()->getLastname(),
                        "email" => $order->getShippingAddress()->getEmail(),
                        "phone" => $order->getShippingAddress()->getTelephone(),
//                        "company" => $order->shipping_company,
                        "address" => $order->getShippingAddress()->getTelephone(),
                        "address2" => $order->getShippingAddress()->getTelephone(),
                        "city" => $order->getShippingAddress()->getCity(),
                        "statename" => $order->getShippingAddress()->getRegionId(),
                        "countryname" => $order->getShippingAddress()->getCountryId(),
                        "zipcode" => $order->getShippingAddress()->getPostcode(),
                    )
                );
                $result = $vboutApp->Order($orderData, 2);
            }
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, 'vbout-ecommerce-marketing.log');
        }
    }
}