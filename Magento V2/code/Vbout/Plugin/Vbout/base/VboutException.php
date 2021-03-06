<?php
namespace Vbout\Plugin\Vbout\base;
use Exception;

class VboutException extends Exception
{
	protected $data;
	 
    public function __construct($data)
    {
		parent::__construct($data['errorMessage']);

		$this->data = $data;
	}

	public function getData()
	{
		return $this->data;
	}
}