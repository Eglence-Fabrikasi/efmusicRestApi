<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
#[\AllowDynamicProperties]

class sales extends TableItem {
	// fields
	public $ID;
    public $contentID;
    public $userID;
	public $salesDate;
	public $packageID;
	public $price;
	public $currency;
	public $completed;
	public $xid;
	public $payerID;
	public $customerID;
	public $channelID;
	public $processType;
	public $upgradeContractID;

	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "sales" );
		$this->refresh ( $ID );
	}
	function __set($property, $value) {
		$this->$property = $value;
	}
	function __get($property) {
		if (isset ( $this->$property )) {
			return $this->$property;
		}
	}

	public static function getSalesFromPayerID($payerID)
	{
		$intc = new self();
		$sql = "select * from sales where payerID='" . $intc->checkInjection($payerID) . "' order by ID desc limit 1";
		$intc->refreshprocedure($sql);
		return $intc;
	}
	
}
?>
