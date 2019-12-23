<?php

/* * ****************************************************
 * Package   : Vbout
 * Author    : MMG
 * Copyright : (c) 2019
 * ***************************************************** */
?>
<?php

require_once Mage::getBaseDir('lib') . '/Vbout/services/EmailMarketingWS.php';

class Vbout_Vbout_Block_Form extends Mage_Core_Block_Template implements Mage_Widget_Block_Interface {

    protected $_form;
    protected $_list;

    protected function _construct() {
        parent::_construct();
        $this->addData(array('cache_lifetime' => 86400));
    }

    public function getCacheKeyInfo() {
        return array(
            'VBOUT_FORM' . rand(),
            Mage::app()->getStore()->getId(),
            Mage::getDesign()->getPackageName(),
            Mage::getDesign()->getTheme('template'),
            Mage::getSingleton('customer/session')->getCustomerGroupId(),
            'template' => $this->getTemplate(),
            $this->getFormId()
        );
    }

    public function getForm() {
        if (!$this->_form) {
            $this->_form = array();
            try {
                $helper = Mage::helper('vbout');
                $authTokens = $helper->getAuthTokens();
                if (is_array($authTokens)) {
                    $em = new EmailMarketingWS($authTokens);
                    $forms = $em->getMyForms();
                    if (isset($forms['count']) && $forms['count']) {
                        foreach ($forms['items'] as $item) {
                            if ($item['id'] != 0 && $item['id'] == $this->getFormId()) {
                                $this->_form = $item;
                                break;
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                Mage::log($e->getMessage(), null, 'vbout-form.log');
            }
        }
        return $this->_form;
    }

    public function getList() {
        if (!$this->_list) {
            $this->_list = array();
            try {
                $helper = Mage::helper('vbout');
                $authTokens = $helper->getAuthTokens();
                if (is_array($authTokens)) {
                    $em = new EmailMarketingWS($authTokens);
                    $list = $em->getMyList($this->getFormId());
                    $this->_list = $list;
                }
            } catch (Exception $e) {
                Mage::log($e->getMessage(), null, 'vbout-form.log');
            }
        }
        return $this->_list;
    }

}
