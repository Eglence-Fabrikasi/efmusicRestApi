<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
class dealTerms extends TableItem {
	// fields
	public $ID;
	public $channel;
	public $channelType;
	public $percent;
	public $country;
	public $isStandart;
	public $userRole;
	public $commissionUserID;
	public $termLimit;
	public $channelID;
	
	
	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "dealTerms" );
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

	function getDealTerms($type,$dtID,$channelID=0) {
		$sql = "select * from dealTerms where 
			(
			($type=1 and ifnull(dealTerms.commissionUserID,0)=0)
			or ($type=2 and ifnull(dealTerms.commissionUserID,0)>0)
			or ($type=0)
			) and
		($dtID=0 or ID=$dtID) and channelID=".$this->checkInjection($channelID)." order by channel";
		return $this->executenonquery($sql,true);
	}

	
	
}
?>
