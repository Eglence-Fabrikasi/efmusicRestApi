<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
class appleImports extends TableItem {
	// fields
	public $ID;
	public $upc;
	public $storeFront;
	public $status;
	public $date_;
	public $userID;

	
	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "appleImports" );
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

	public static function getAppleImportsFromUpc ($upc,$userID) {
		$intc = new self();
		$upc = $intc->checkInjection($upc);
		$userID = $intc->checkInjection($userID);
	    $intc->refreshProcedure("select * from appleImports where upc='$upc' and userID=$userID and status=1 limit 1");
	    return $intc;
	}
	
}
?>