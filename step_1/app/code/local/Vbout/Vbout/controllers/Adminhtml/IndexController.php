<?php

/* * ****************************************************
 * Package   : Vbout
 * Author    : MMG
 * Copyright : (c) 2019
 * ***************************************************** */
?>
<?php

require_once Mage::getBaseDir('lib') . '/Vbout/services/WebsiteTrackWS.php';

class Vbout_Vbout_Adminhtml_IndexController extends Mage_Adminhtml_Controller_Action {

    public function getTrackerCodeAction() {
        $result = array();
        $result['trackercode'] = '';
        if ($this->getRequest()->isPost()) {
            $params = $this->getRequest()->getPost();
            $helper = Mage::helper('vbout');
            $authTokens = $helper->getAuthTokens();
            if (is_array($authTokens)) {
                $tracking = new WebsiteTrackWS($authTokens);
                $domains = $tracking->getDomains();
                if (isset($domains['count']) && $domains['count']) {
                    foreach ($domains['items'] as $item) {
                        if ($item['id'] != 0) {
                            if ($params['domain_id'] == $item['id']) {
                                $result['trackercode'] = htmlentities(base64_decode($item['trackercode']));
                            }
                        }
                    }
                }
            }
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

}
