<?php
require_once __DIR__ . '/autoload.php';

class modRetailCrm extends \RetailCrm\ApiClient
{
	public $modx;

	protected $url;

	protected $apiKey;

	protected $siteCode;

	/*$client = new \RetailCrm\ApiClient(
	    'https://minishop.retailcrm.ru',
	    'lxMy7hMo9sCWn1VEu6Yia4bQhfWMS9Ey',
	    \RetailCrm\ApiClient::V5
	);	*/

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