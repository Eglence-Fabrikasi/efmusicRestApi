<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
class marketingContacts extends TableItem {
	// fields
	public $ID;
	public $customer;
	public $company;
	public $email;
	public $phone;
	public $date_;
	public $contactType;
	

	
	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "marketingContacts" );
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

	function getMarketingContacts () {
		$sql = "call getMarketingContacts()";
		return $this->executenonquery($sql,true);

	}
}
?>
