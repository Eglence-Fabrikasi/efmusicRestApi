<?php
require_once dirname(dirname(dirname(__FILE__))) . "/DL/DAL.php";

use data\TableItem;
#[\AllowDynamicProperties]


class roles extends TableItem
{
	// fields
	public $ID;
	public $role;
	public $roleType;
	public $isAdmin;


	// Counctructor
	function __construct($ID = NULL)
	{
		parent::__construct();
		$this->ID = $ID;
		$this->settable("roles");
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

	function getRoles ($roleType) {
		$sql = "select * from roles where 0=$roleType or roleType=$roleType order by ID";
		return $this->executenonquery($sql,true);
	}

	
}
