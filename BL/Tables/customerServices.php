<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
class customerServices extends TableItem {
	// fields
	public $ID;
	public $customerID;
	public $serviceID;
	public $date_;
	public $createdBy;
	
	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "customerServices" );
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
	function getCustomerServices () {
		$sql = "select * from customerServices";
		return $this->executenonquery($sql,true);
	}
}
?>
