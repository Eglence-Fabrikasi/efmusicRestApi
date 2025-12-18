<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
#[\AllowDynamicProperties]

class artistRoles extends TableItem {
	// fields
	public $ID;
    public $role;
    public $contentTypeID;
	public $isInstrument;
	public $spotify;



	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "artistRoles" );
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

	public static function getRoleID($roleText, $contentTypeId) {
	    $intc = new self();
	    $intc->refreshProcedure("select * from artistRoles where role = '$roleText' and contentTypeID = $contentTypeId");
	    return $intc;
	}
	
}
?>
