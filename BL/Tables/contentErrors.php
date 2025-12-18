<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
#[\AllowDynamicProperties]

class contentErrors extends TableItem {
	// fields
	public $ID;
	public $contentID;
	public $contentErrorID;
	public $comment;
	public $dateCreated;
	public $sessionID;
	public $userID;
	public $ticketType; // 1 = Content , 2 = Platform, 3 = Payment
	public $status;
	public $closeUserID;
	public $closeDate;

	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "contentErrors" );
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

	function getTickets ($userID, $albumID) {
		$sql = "call pgetTickets($userID, $albumID)";
		return $this->executenonquery($sql,true);
	}

	function getTicketsbySessionID ($sessionID) {
		$sql = "call pgetTicketsbySessionID('$sessionID')";
		return $this->executenonquery($sql,true);
	}

	function ticketClose ($sessionID, $userID) {
		$sql = "update contentErrors set status = 3,closeUserID = $userID,closeDate = now() where sessionID='$sessionID';";
		return $this->executenonquery($sql, false, true);
	}

	function ticketMarkasFixed ($contentID) {
		$sql = "update contentErrors set status = 2 where contentID = $contentID  and status < 3;";
		return $this->executenonquery($sql, false, true);
	}
	
	function contentTicketsClose ($contentID,$userID) {
		$sql = "update contentErrors set status = 3,closeUserID = $userID,closeDate = now() where contentID = $contentID;";
		return $this->executenonquery($sql, false, true);
	}
	public static function getOldSessionID ( $contentID) {
		$intc = new self();
		$intc->refreshprocedure("select * from contentErrors where contentID = $contentID and status < 3 limit 1;");
		return $intc;
	}
}
?>
