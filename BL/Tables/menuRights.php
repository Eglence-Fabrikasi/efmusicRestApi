<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
#[\AllowDynamicProperties]

class menuRights extends TableItem {
	// fields
	public $ID;
	public $menuID;
	public $userTypeID;
	public $action;
	public $comment;

	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "menuRights" );
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

	function getUserRole ($userID) {
		$sql = "call getUserRole ($userID)";
		return $this->executenonquery($sql,true);
	}

	
}
?>
