<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
#[\AllowDynamicProperties]

class country extends TableItem {
	// fields
	public $ID;
	public $name;
	public $country;
	public $currencyID;
	public $continent;
	public $listOrder;
	public $currencyCode;
	public $countryRating;

	
	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "country" );
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

	function getCountries () {
		$sql = "SELECT ID, name, country, currencyID, continent, listOrder, currencyCode, countryRating FROM country order by name;";
		return $this->executenonquery($sql,true);
	}	

	function getCountry ($query) {
		$sql = "select distinct country.* from country left join localization on localization.identifier = country.name where localization.label like '$query%' or country.name like '$query%';";
		return $this->executenonquery($sql,true);
	}		
	
}
?>
