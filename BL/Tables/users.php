<?php
require_once dirname(dirname(dirname(__FILE__))) . "/DL/DAL.php";
use data\TableItem;
#[\AllowDynamicProperties]

class users extends TableItem
{
	// fields
	public $ID;
	public $username;
	public $password;
	public $fullname;
	public $email;
	public $status;
	public $roleID;
	public $clang;
	public $isDeleted;
	public $customerID;
	public $userType;
	//public $paymentCurrency; 
	//public $vendorName; 
	//public $isIndividual;
	//public $contractId; 
	//public $channelID; 
	public $phone;
	public $address;
	public $countryID;
	public $date_;
	public $createdBy;
	public $subLabelRate;
	public $artistID;	

	// Counctructor
	function __construct($ID = NULL)
	{
		parent::__construct();
		$this->ID = $ID;
		$this->settable("users");
		$this->refresh($ID);
	}
	function __set($property, $value)
	{
		$this->$property = $value;
	}
	function __get($property)
	{
		if (isset($this->$property)) {
			return $this->$property;
		}
	}

	public static function getAuth($userName, $password)
	{
		$intc = new self();
		$sql = "call getAuth('" . $intc->checkInjection($userName) . "','" . $intc->checkInjection($password) . "')";
		$intc->refreshprocedure($sql);
		return $intc;
	}

	public static function getUserFromUserName($userName)
	{
		$intc = new self();
		$sql = "call getUserFromUserName('" . $intc->checkInjection($userName) . "')";
		$intc->refreshprocedure($sql);
		return $intc;
	}

	function randomPassword()
	{
		$alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
		$pass = array(); //remember to declare $pass as an array
		$alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
		for ($i = 0; $i < 8; $i++) {
			$n = rand(0, $alphaLength);
			$pass[] = $alphabet[$n];
		}
		return implode($pass); //turn the array into a string
	}

	function getUsers($userID)
	{
		$sql = "call getUsers (" . $this->checkInjection($userID) . ")";
		//echo $sql;
		return $this->executenonquery($sql, true);
	}

	function getUserByEmail($email)
	{
		$sql = "call pgetUserByEmail('".$this->checkInjection($email)."');";
		return $this->executenonquery($sql, true);
	}	

	public static function getEmailFromCustomer($customerID)
	{
		$intc = new self();
		$sql = "SELECT * FROM users where users.roleID=6 and ifnull(users.isDeleted,0)<>1 and users.customerID=".$customerID." order by ID limit 1";
		$intc->refreshprocedure($sql);
		return $intc;
	}


}
