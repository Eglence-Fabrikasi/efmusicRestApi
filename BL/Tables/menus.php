<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
#[\AllowDynamicProperties]

class menus extends TableItem {
	// fields
	public $ID;
	public $title;
	public $menu;
	public $parentID;
	public $icon;
	public $ab;
	public $isVisible;
	public $position;
	

		
	
	
	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "menus" );
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

	function getMenus ($menuID) {
		$sql = "call getMenus($menuID)";
		return $this->executenonquery($sql,true);
	}
	
}
?>
