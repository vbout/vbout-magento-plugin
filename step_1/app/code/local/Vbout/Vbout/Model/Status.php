<?php

/* * ****************************************************
 * Package   : Vbout
 * Author    : MMG
 * Copyright : (c) 2019
 * ***************************************************** */
?>
<?php

class Vbout_Vbout_Model_Status extends Varien_Object {

    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 2;

    static public function getOptionArray() {
        return array(
            self::STATUS_ENABLED => Mage::helper('vbout')->__('Enabled'),
            self::STATUS_DISABLED => Mage::helper('vbout')->__('Disabled')
        );
    }

}
