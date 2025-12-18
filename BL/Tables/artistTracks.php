<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
#[\AllowDynamicProperties]

class artistTracks extends TableItem {
	// fields
	public $ID;
	public $trackID;
	public $artistID;
	public $roleID;
	public $primary;
	public $userID;
	public $date_;
		
	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "artistTracks" );
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
	function getTrackArtists ($trackID, $userID, $customerID) {
		$sql = "call getTrackArtists($trackID, $userID, $customerID)";
		return $this->executenonquery($sql,true);


	}
	public static function getArtistTrackID ($artistID, $trackID) {
	    $intc = new self();
	    $intc->refreshProcedure("select * from artistTracks where artistID  = $artistID and trackID = $trackID");
	    return $intc;
	}

	public static function getArtistTrackIDwithRole ($artistID, $trackID,$roleID) {
	    $intc = new self();
	    $intc->refreshProcedure("select * from artistTracks where artistID  = $artistID and trackID = $trackID and roleID=$roleID");
	    return $intc;
	}
}
?>
