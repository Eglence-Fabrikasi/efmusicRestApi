<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
class customerContracts extends TableItem {
	// fields
	public $ID;
	public $customerID;
	public $contractID;
	public $termDate;
	public $term;
	public $rate;
	public $commissionID;
	public $isSent;
	public $isSigned;
	//public $isDefault;
	public $parentID;
	public $dealTermID;
	public $isDeleted;
	public $userID;
	public $date_;
	public $contractApprovalDate;	
	public $endBy;	
	public $endDate;	
	public $endDescription;	

	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "customerContracts" );
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
	function getContracts ($customerID) {

		$sql = "call getCustomerContracts($customerID)";
		return $this->executenonquery($sql,true);

	}
	public static function getCustomerContractID ($customerID, $contractID) {
	    $intc = new self();
	    $intc->refreshProcedure("select * from customerContracts where customerID  = $customerID and contractID = $contractID and isDeleted = 0");
	    return $intc;
	}

	public static function getCustomerContract($customerID) {
	    $intc = new self();
	    $intc->refreshProcedure("select * from customerContracts where customerID  = $customerID and isDeleted = 0 order by ID desc limit 1");
	    return $intc;
	}

	function setContractSign($customerID) {
		$sql = "update customerContracts set isSigned = 1, contractApprovalDate = now() where customerID = $customerID and ifnull(isSigned, 0) = 0 ";
		return $this->executenonquery($sql, false, true);		
	}

	function setContractPay($customerID) {
		$sql = "update customerContracts set termDate = now() where customerID = $customerID and ifnull(isDeleted, 0) = 0 ";
		return $this->executenonquery($sql, false, true);		
	}

	function setUsersStatus($contractID) {
		$sql = "call psetUsersStatus($contractID) ";
		return $this->executenonquery($sql, false, true);		
	}
	
}
?>
