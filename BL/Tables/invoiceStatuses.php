<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
class invoiceStatuses extends TableItem {
	// fields
	public $ID;
	public $processDate;
	public $scopeDate;
	public $status;
	public $statusDate;


	
	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "invoiceStatuses" );
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

	public static function getInvoiceStatusFromDate($scopeDate)
	{
		$intc = new self();
		$sql = "select * from invoiceStatuses where scopeDate='" . $intc->checkInjection($scopeDate) . "'";
		$intc->refreshprocedure($sql);
		return $intc;
	}

	public static function getInvoiceStatusForCreate()
	{
		$intc = new self();
		$sql = "select * from invoiceStatuses where status=3 order by ID limit 1";
		$intc->refreshprocedure($sql);
		return $intc;
	}
	
}
?>
