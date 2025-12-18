<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
#[\AllowDynamicProperties]

class artistAlbums extends TableItem {
	// fields
	public $ID;
	public $albumID;
	public $artistID;
	public $roleID;
	public $characterName; // db aktarimindan sonra eklendi.Onder abiye sorulacak. 
	public $artistType;
	public $primary;
	public $date_;
	public $userID;
		
	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "artistAlbums" );
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
	function getAlbumArtists ($albumID , $userID, $customerID) {
		$sql = "call getAlbumArtists($albumID, $userID, $customerID)";
		$this->executenonquery($sql,true);


	}
	public static function getArtistAlbumsID ($artistID, $albumID) {
	    $intc = new self();
	    $intc->refreshProcedure("select * from artistAlbums where artistID  = $artistID and albumID = $albumID");
	    return $intc;
	}
	function delAlbumArtists ($albumID) {
		$sql = "delete from artistAlbums where albumID = $albumID";
		return $this->executenonquery($sql,true);


	}

	public static function getArtistAlbumsIDWithRole ($artistID, $albumID,$roleID) {
	    $intc = new self();
	    $intc->refreshProcedure("select * from artistAlbums where artistID  = $artistID and albumID = $albumID and roleID=$roleID");
	    return $intc;
	}
}
?>
