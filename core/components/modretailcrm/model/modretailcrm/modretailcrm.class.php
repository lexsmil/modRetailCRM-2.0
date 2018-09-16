<?php
require_once __DIR__ . '/autoload.php';

class modRetailCrm extends \RetailCrm\ApiClient
{
	public $modx;

	protected $url;

	protected $apiKey;

	protected $siteCode;


	public function __construct(modX &$modx, $apiKey = '', $crmurl = '', $siteCode = '')
	{		
		$this->modx =& $modx;
		$this->apiKey = $apiKey;
		$this->url = $crmurl;
		$this->siteCode = $siteCode;
		if(!empty($apiKey)){
			parent::__construct($this->url, $this->apiKey, 'v5', $this->siteCode);
		}
		
	}
	
}