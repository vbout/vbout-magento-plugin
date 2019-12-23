<?php

/* * ****************************************************
 * Package   : Vbout
 * Author    : MMG
 * Copyright : (c) 2019
 * ***************************************************** */
?>
<?php

class Vbout_Vbout_Model_System_Config_Source_Customer_Attribute {

    protected $_options;

    public function toOptionArray() {
        if (!$this->_options) {
            $this->_options = array();
            $this->_options[0] = array('value' => '', 'label' => Mage::helper('adminhtml')->__(''));
            try {
                $attributes = Mage::getModel('customer/customer')->getAttributes();
                foreach ($attributes as $attr) {
                    if ($attr->getStoreLabel() != '') {
                        $this->_options[] = array('value' => $attr->getAttributeCode(), 'label' => $attr->getStoreLabel());
                    }
                }
            } catch (Exception $e) {
                Mage::log($e->getMessage(), null, 'vbout-config.log');
            }
        }
        return $this->_options;
    }

}
