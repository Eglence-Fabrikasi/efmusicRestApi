<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
#[\AllowDynamicProperties]

class currency extends TableItem {
	// fields
	public $ID;
	public $currency;
	
	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "currency" );
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

	function getCurrencies () {
		$sql = "SELECT currency.ID, currency.currency FROM currency order by currency.currency;";
		return $this->executenonquery($sql,true);
	}		
	
}
?>
