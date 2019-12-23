<?php

namespace Vbout\Plugin\Vbout\services;
use Vbout\Plugin\Vbout\base\Vbout;
use Vbout\Plugin\Vbout\base\VboutException;

class ApplicationWS extends Vbout
{
	protected function init()
	{
		$this->api_url = '/app/';
	}
	
    public function getBusinessInfo()
    {	
		$result = array();
		
		try {
			$business = $this->me();

            if ($business != null && isset($business['data'])) {
                $result = array_merge($result, $business['data']['business']);
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
}