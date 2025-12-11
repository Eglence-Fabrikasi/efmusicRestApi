<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
class ticketErrorNames extends TableItem {
	// fields
	public $ID;
	public $name;
	// public $contentID;
	// public $contentErrorID; // 8 = Meta , 9 = Art, 10 = Audio
	// public $comment;
	// public $dateCreated;
	// public $sessionID;
	// public $userID;
	public $ticketType;
	
	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "ticketErrorNames" );
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
