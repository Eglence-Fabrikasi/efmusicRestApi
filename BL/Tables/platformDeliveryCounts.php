<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
#[\AllowDynamicProperties]

class platformDeliveryCounts extends TableItem {
	// fields
	public $ID;
	public $platformID;
	public $contentID;
	public $date_;
	public $userID;
	

	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "platformDeliveryCounts" );
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

	
}
?>
