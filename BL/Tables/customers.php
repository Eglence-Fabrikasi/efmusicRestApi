<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
class customers extends TableItem {
	// fields
	public $ID;
	public $customer;
	public $customerType;
	public $parentCustomerID;
	public $countryID;
	public $paymentCurrency;
	// public $enableMusic;
	// public $enableMusicVideo;
	// public $enableMovie;
	public $enableBooklet;	
	// public $manageYouTube;
	public $enableClaims;
	public $enableIndividual;
	// public $efChannelID;
	public $isDeleted;
	public $date_;
	public $userID;
	public $channelID;
	// public $vendorName;	
	public $phone;
	public $address;
	// public $allowBooklet;
	// public $allowMusicVideo;
	public $bankName;
	public $bankIban;
	public $twitterUrl;
	public $facebookUrl;
	public $instagramUrl;

	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "customers" );
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

	// function getCustomers ($customerID) { 
	// 	$sql = "call getCustomers($customerID)";
	// 	return $this->executenonquery($sql, true);
	// }

	function getCustomer ($customerID,$userID=0) {
		$sql = "call getCustomer($customerID,$userID)";
		return $this->executenonquery($sql, true);
	}	

	function getCustomerUsers ($customerID) {
		$sql = "call pgetCustomerUsers($customerID)";
		return $this->executenonquery($sql, true);
	}	

	function searchCustomer ($customerName) {
		$sql = "select ID, customer from customers where customer like '%$customerName%' and ifnull(isDeleted, 0) = 0";
		return $this->executenonquery($sql, true);
	}	

	function customerContractUpgrade ($userID, $customerID, $contractID) {
		$sql = "call pCustomerContractUpgrade($userID, $customerID, $contractID)";
		return $this->executenonquery($sql, true);
	}

	function customerContractSuperSead ($userID, $customerID, $contractID) {
		$sql = "call pCustomerContractSuperSead($userID, $customerID, $contractID)";
		return $this->executenonquery($sql, true);
	}	

	function customerContractSign ($customerID, $contractID) {
		$sql = "update customerContracts set isSigned = 1 where contractID = $contractID and customerID = $customerID and ifnull(isDeleted, 0) = 0";
		return $this->executenonquery($sql, null,true);
	}		
	
}
?>
