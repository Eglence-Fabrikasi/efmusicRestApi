<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
#[\AllowDynamicProperties]

class languages extends TableItem {
	// fields
	public $ID;
	public $name;
	public $code;
	public $isDisplayLanguage;
	public $isDefault;
	public $dateCreated;
	public $createdBy;
	public $icon;
	public $status;
	public $contentType;
	
	
	
	
	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "languages" );
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

	function getLanguages() {
		$sql = "select * from languages where ifnull(isDisplayLanguage, 0) = 1 order by name";
		return $this->executenonquery($sql,true);
	}

	public static function getLanguageID($language) {
	    $intc = new self();
	    $intc->refreshProcedure("select * from languages where code = '$language' and contentType is null limit 1");
	    return $intc;
	}
	
	public static function getLanguageIDwithContentType($language,$contentType=1) {
	    $intc = new self();
	    $intc->refreshProcedure("select * from languages where code = '$language' and contentType=$contentType limit 1");
	    return $intc;
	}
}
?>
