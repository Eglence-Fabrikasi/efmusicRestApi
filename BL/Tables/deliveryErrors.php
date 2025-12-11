<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
class deliveryErrors extends TableItem {
	// fields
	public $ID;
	public $contentID;
	public $deliveryID;
	public $deliveryErrorID;
	public $platformID;
	public $comment;
	public $dateCreated;
	public $customerID;
	
	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "deliveryErrors" );
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
