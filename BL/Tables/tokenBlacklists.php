<?php
require_once dirname(dirname(dirname(__FILE__))) . "/DL/DAL.php";

use data\TableItem;
class tokenBlacklists extends TableItem
{
	// fields
	# concat('public $',column_name,';')
	public $ID;
	public $ctoken;
	public $expiresAt;
	
	

	// Counctructor
	function __construct($ID = NULL)
	{
		parent::__construct();
		$this->ID = $ID;
		$this->settable("tokenBlacklists");
		$this->refresh($ID);
	}
	function __set($property, $value)
	{
		$this->$property = $value;
	}
	function __get($property)
	{
		if (isset($this->$property)) {
			return $this->$property;
		}
	}

	public static function getToken ($token) {
		$intc = new self();
		$sql = "select * from tokenBlacklists where ctoken='" . $intc->checkInjection($token) . "' limit 1";
		$intc->refreshprocedure($sql);
		return $intc;
	}
}
