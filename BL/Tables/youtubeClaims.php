<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
#[\AllowDynamicProperties]

class youtubeClaims extends TableItem {
	// fields
	public $ID;
    public $assetID;
	public $contentType;
	public $claimID;
	public $isPartnerUploaded;
	public $kind;
	public $status;
	public $thirdPartyClaim;
	public $timeCreated;
	public $timeStatusLastModified;
	public $videoID;
	public $videoTitle;
	public $videoViews;
	public $originSource;
	public $date_;	

	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "youtubeClaims" );
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
	
}
?>
