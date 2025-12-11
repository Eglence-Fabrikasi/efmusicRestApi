<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
class creditCardBins extends TableItem {
	// fields
	public $ID;
    public $bankCode;
    public $bank;
	public $bin;
	public $organization;

	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "creditCardBins" );
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

	public static function pcheckCreditCardBin ($bin) {
	    $intc = new self();
	    $intc->refreshProcedure("call pcheckCreditCardBin('$bin')");
	    return $intc;
	}

	
	
}
?>
