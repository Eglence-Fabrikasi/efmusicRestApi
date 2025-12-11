<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
class linkFires extends TableItem {
	// fields
	public $ID;
	public $albumID;
	public $url;
	public $dataId;
	public $code;
	public $transactionId;
	public $date_;
	public $lt;
	public $rescanTransactionId;

	// Constructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "linkFires" );
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

	public static function getLinkfire ($albumID, $lt) {
	    $intc = new self();
		$albumID=$intc->checkInjection($albumID);
		$lt=$intc->checkInjection($lt);
	    $intc->refreshProcedure("select * from linkFires where albumID=$albumID and lt=$lt");
	    return $intc;
	}

}
?>
