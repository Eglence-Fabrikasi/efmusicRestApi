<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
class invoices extends TableItem {
	// fields
	public $ID;
	public $customerID;
	public $endDate;
	public $processDate;
	public $periot;
	public $platform;
	public $currency;
	public $amount;
	public $accountCurrency;
	public $rate;
	public $accountPaid;
	public $accountPaidwithoutVat;
	public $invoice;
	public $paid;
	public $scopeDate;
	public $invoiceStatusID;
	public $scopeShortDate;
	public $EFShare;
	public $unit;
	public $isCommission;
	public $date_;
	
	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "invoices" );
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
