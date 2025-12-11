<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
class trackISRCs extends TableItem {
	// fields
	public $ID;
	public $trackID;
	public $isrc;
	public $isrcType;
	public $date_;
	public $assetFile;
	public $filesize;
	public $MD5;


	
	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "trackISRCs" );
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

	public static function getTrackISRC($trackID,$isrcType)
	{
		$intc = new self();
		$sql = "SELECT * FROM trackISRCs where trackISRCs.trackID=".$trackID." and isrcType=".$isrcType." order by ID limit 1";
		$intc->refreshprocedure($sql);
		return $intc;
	}

		
	
}
?>
