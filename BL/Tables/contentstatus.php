<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
class contentStatus extends TableItem {
	// fields
	public $ID;
	public $contentID;
	public $deliveryID;
	public $status;
	public $platformID;
	public $dateCreated;
	public $userID;
	public $sessionID;
	public $salesStartDate;
	public $albumPrice;
	public $trackPrice;
	public $appleDigitalMaster;
	public $allowPreOrder;
	public $allowPreOrderPreview;
	public $preOrderDate;
	public $soundEngineerEmailAddress;
	public $prioty;
	
	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "contentStatus" );
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

	public static function contentStatusWithPlatform($contentID,$platformID,$status)
	{
		$intc = new self();
		$sql = "select * from contentStatus where contentID=".$contentID." and platformID=".$platformID." and status=".$status." order by ID desc limit 1";
		$intc->refreshProcedure($sql);
		return $intc;
	}

	
}
?>
