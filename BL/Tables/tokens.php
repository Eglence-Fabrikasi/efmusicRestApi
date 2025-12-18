<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
#[\AllowDynamicProperties]

class tokens extends TableItem {
	// fields
	public $ID;
	public $platformID;
	public $platformToken;
	public $date_;
	
	
	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "tokens" );
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
	
    public static function getTokenFromPlatform ($platformID) {
        $intc = new self();
        $intc->refreshProcedure("SELECT * from tokens where platformID=$platformID");
        return $intc;
      
    }
}
?>