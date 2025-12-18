<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
#[\AllowDynamicProperties]

class trackIDMaster extends TableItem {
	// fields
	public $ID;
	public $platform;
	public $albumsTrackID;
	public $isrc;
	public $date_;
	public $platformID;
	public $isDeleted;
	

	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "trackIDMaster" );
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

	public static function getTrackIDMaster($isrc,$platformID)
	{
		$intc = new self();
		$sql = "SELECT * FROM trackIDMaster where isrc='$isrc' and platform=$platformID order by ID desc limit 1";
		$intc->refreshprocedure($sql);
		return $intc;
	}
	
}
?>
