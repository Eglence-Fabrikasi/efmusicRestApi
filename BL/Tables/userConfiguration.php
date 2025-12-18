<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
#[\AllowDynamicProperties]

class userConfiguration extends TableItem {
	// fields
	public $ID;
	public $userID;
	public $sideBarMini;
	public $menuColor;
	
	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "userConfiguration" );
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

	function setUserConf($userID, $color, $menu)
	{
		$userID =  $this->checkInjection($userID) ;
		$color =  $this->checkInjection($color) ;
		$menu =  $this->checkInjection($menu) ;

		$sql = "call psetUserConf($userID, '$color', $menu);";
		return $this->executenonquery($sql, null, true);
	}	
	
}
?>
