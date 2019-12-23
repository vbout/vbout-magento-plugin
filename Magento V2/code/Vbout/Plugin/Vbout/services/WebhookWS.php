<?php

namespace Vbout\Plugin\Vbout\services;
use Vbout\Plugin\Vbout\base\Vbout;
use Vbout\Plugin\Vbout\base\VboutException;

class WebhookWS extends Vbout 
{
	protected function init()
	{
		$this->api_url = '/webhook/';
	}
	
	public function getWebhooks($params = array())
    {	
		$result = array();
		
		try {
			$webhooks = $this->lists($params);

            if ($webhooks != null && isset($webhooks['data'])) {
                $result = array_merge($result, $webhooks['data']['webhooks']);
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function getWebhook($params = array())
    {	
		$result = array();
		
		try {
			$webhook = $this->show($params);

            if ($webhook != null && isset($webhook['data'])) {
                $result = array_merge($result, $webhook['data']['item']);
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function removeWebhook($params = array())
    {	
		$result = array();
		
		try {
			$webhook = $this->delete($params);

            if ($webhook != null && isset($webhook['data'])) {
                $result = $webhook['data']['item'];
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function addNewWebhook($params = array())
    {	
		$result = array();
		
		try {
			$this->set_method('POST');
			
			$webhook = $this->add($params);

            if ($webhook != null && isset($webhook['data'])) {
                $result = $webhook['data']['item'];
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function updateWebhook($params = array())
    {	
		$result = array();
		
		try {
			$this->set_method('POST');
			
			$webhook = $this->edit($params);

            if ($webhook != null && isset($webhook['data'])) {
                $result = $webhook['data']['item'];
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }

}