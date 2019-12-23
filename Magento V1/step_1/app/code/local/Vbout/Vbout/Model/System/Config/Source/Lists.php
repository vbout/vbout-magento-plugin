<?php

/* * ****************************************************
 * Package   : Vbout
 * Author    : MMG
 * Copyright : (c) 2019
 * ***************************************************** */
?>
<?php

require_once Mage::getBaseDir('lib') . '/Vbout/services/EmailMarketingWS.php';

class Vbout_Vbout_Model_System_Config_Source_Lists {

    protected $_options;

    public function toOptionArray() {
        if (!$this->_options) {
            $this->_options = array();
            $this->_options[0] = array('value' => '0', 'label' => Mage::helper('adminhtml')->__(''));
            try {
                $helper = Mage::helper('vbout');
                $authTokens = $helper->getAuthTokens();
                if (is_array($authTokens)) {
                    $em = new EmailMarketingWS($authTokens);
                    $lists = $em->getMyLists();
                    if (isset($lists['count']) && $lists['count']) {
                        foreach ($lists['items'] as $item) {
                            if ($item['id'] != 0) {
                                $this->_options[] = array('value' => $item['id'], 'label' => $item['name']);
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                Mage::log($e->getMessage(), null, 'vbout-config.log');
            }
        }
        return $this->_options;
    }

}
