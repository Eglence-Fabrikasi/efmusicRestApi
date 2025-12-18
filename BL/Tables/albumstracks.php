<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
#[\AllowDynamicProperties]

class albumsTracks extends TableItem {
	// fields
	public $ID;
	public $albumID;
	public $trackID;
	public $trackOrder;
	public $date_;
	public $userID;

	
	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "albumsTracks" );
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
	function oldAlbusTracks () {
		$sql = "select trackId,albumId,position,dateCreated from albumstracks order by albumId,position";
		return $this->executenonquery($sql,true);
	}
	function getTrackAlbums ($customerID , $trackID) {
		$sql = "call pgetTrackAlbums($customerID , $trackID)";
		return $this->executenonquery($sql,true);
	}

	function getAlbumsTracks ($albumID) {
		$sql ="select trackID from albumsTracks where albumID=".$albumID." order by ID";
		return $this->executenonquery($sql);
	}

	public static function getAlbumTracksFromTrack ($albumID, $trackID) {
	    $intc = new self();
	    $intc->refreshProcedure("select * from albumsTracks where albumID=$albumID and trackID=$trackID order by ID desc limit 1");
	    return $intc;
	}

	
}
?>
