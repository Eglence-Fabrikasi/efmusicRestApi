<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
#[\AllowDynamicProperties]

class albumIDMaster extends TableItem {
	// fields
	public $ID;
    public $platform;
    public $albumID;
	public $upc;
	public $platformID;
	public $subscriber;
	public $isDeleted;
	public $userID;
	public $date_;

	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "albumIDMaster" );
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
