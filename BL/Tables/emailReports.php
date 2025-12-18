<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
#[\AllowDynamicProperties]

class emailReports extends TableItem {
	// fields
	public $ID;
	public $userID;
	public $emailTypeID;
	public $period;
	public $date_;
	public $isDeleted;
	
	// Constructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "emailReports" );
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
	function getEmailReports ($userID) {
		//$sql = "select ID, userId, emailTypeId, period, date_ from emailReports where userId = $userID and ifnull(isDeleted, 0) = 0 order by ID desc limit 1;";
		$sql = "call pgetUserEmailReports($userID);";
		return $this->executenonquery($sql, true);
	}
	function deleteEmailReports ($userID, $emailTypeID) {
		$sql = "delete from emailReports where userID = $userID and emailTypeID = $emailTypeID";
		return $this->executenonquery($sql, false, true );
	}	

	public static function getEmailReportsFromCustomer($customerID)
	{
		$intc = new self();
		$sql = "select * from emailReports where emailTypeID=6 and emailReports.userID in (select users.ID from users where customerID=".$customerID.") and isDeleted<>1 order by date_ desc limit 1";
		$intc->refreshprocedure($sql);
		return $intc;
	}
}
?>
