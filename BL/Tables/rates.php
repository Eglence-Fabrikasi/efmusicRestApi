<?php
require_once dirname(dirname(dirname(__FILE__)))."/DL/DAL.php";
use data\TableItem;
#[\AllowDynamicProperties]


class rates extends TableItem
{
	//fields
	public $ID;
	public $rateID;
	Public $exchange;
	public $exchangeDate;
	public $userID;
	public $date_;
	Public $isDeleted;
	// Counctructor
	function __construct($ID=NULL)
	{
		parent::__construct();
		$this->ID=$ID;
		$this->settable("rates");
		$this->refresh($ID);
	}
	function __set($property, $value)
	{
		$this->$property = $value;
	}
	function __get($property)
	{
		if (isset($this->$property))
		{
			return $this->$property;
		}
	}
	
	public static function getRate ($rateID)
	{
	    $intc = new self();
	    $intc->refreshProcedure("select * from rates where rateID=".$rateID. " order by exchangeDate desc limit 1");
	    return $intc;
	}
	
	
}
?>