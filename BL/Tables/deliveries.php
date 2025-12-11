<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
class deliveries extends TableItem {
	// fields
	public $ID;
	public $albumID;
	public $platformID;
	public $XMLvalue;
	public $batchLocation;
	public $dateCreated;
	public $userID;
	public $deliveryLog;
	public $date_;
	
	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "deliveries" );
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

	function getDeliveries($limit)
	{
		$sql = "call getDeliveryQueues (" . $this->checkInjection($limit) . ")";
		//echo $sql;
		return $this->executenonquery($sql, true);
	}
	
}
?>
