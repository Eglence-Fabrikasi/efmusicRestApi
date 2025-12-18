<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
#[\AllowDynamicProperties]

class musixmatchDeliveries extends TableItem {
	// fields
	public $ID;
    public $trackID;
	public $senddate_;
	public $isDeleted;



	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "musixmatchDeliveries" );
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

	public static function getMMFromTrack($trackID)
	{
		$intc = new self();
		$sql = "select * from musixmatchDeliveries where trackID=" . $intc->checkInjection($trackID) . " order by ID desc limit 1";
		$intc->refreshprocedure($sql);
		return $intc;
	}



	
}
?>
