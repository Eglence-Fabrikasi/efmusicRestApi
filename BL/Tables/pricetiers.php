<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
#[\AllowDynamicProperties]

class pricetiers extends TableItem {
	// fields
	public $ID;
	public $tier;
	public $price;
	public $type;
	public $dateCreated;
	public $createdBy;
	public $status;
	public $isDefault;
	public $google;
	public $contentTypeID;
	public $priceValue;

	
	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "pricetiers" );
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

	public static function getPriceID($priceText, $contentTypeId) {
	    $intc = new self();
	    $intc->refreshProcedure("select * from priceTiers where tier = '$priceText' and contentTypeID = $contentTypeId");
	    return $intc;
	}
	
}
?>
