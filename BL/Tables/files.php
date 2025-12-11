<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
class files extends TableItem {
	// fields
	public $ID;
    public $title;
	public $tableName;
	public $tableID;
	public $fileName;
	public $date_;
	public $userID;



	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "files" );
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

	function getFiles ($tableName,$tableID) {
		$sql = "select * from files where tableName='$tableName' and tableID=$tableID order by ID desc";
		return $this->executenonquery($sql,true);
	}
	
}
?>
