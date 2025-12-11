<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
class services extends TableItem {
	// fields
	public $ID;
	public $name;
	public $des;
	public $exclude;
	public $isDistribution;
	public $isDeleted;
	//public $desc;
	
	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "services" );
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

	function getServices ($UserID, $serviceID) {
		$sql = "call getServices($UserID, $serviceID)";
		return $this->executenonquery($sql,true);
	}
	
}
?>
