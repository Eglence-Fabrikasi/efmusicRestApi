<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
#[\AllowDynamicProperties]

class contracts extends TableItem {
	// fields
	public $ID;
	public $contentType;
	public $version;
	public $note;
	public $file;
	public $dateAdded;
	public $addedBy;
	public $isDefault;
	public $parentID;
	public $isDeleted;
	public $countryID;
	public $isSelfInvoice;
	public $price;
	public $description;
	public $color;
	public $maxArtist;
	public $contractOrder;
	public $currencyID;
	public $channelID;	
	public $changeDate;
	public $modifiedBy;
	public $isRenew;

	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "contracts" );
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

	function getContractsByChannelID($channelID, $countryID)
	{
		//$sql = "select contracts.*, currency.currency from contracts left join currency on contracts.currencyId = currency.ID where ( $channelID = 0 or channelID = $channelID ) and ( $countryID = 0 or countryID = $countryID ) and ifnull(isDeleted, 0) = 0 and IFNULL(parentID, 0) = 0 order by ifnull(contracts.contractOrder, 0)";
		$sql = "call getContractsByChannelID($channelID, $countryID);";
		return $this->executenonquery($sql, true);
	}		

	function getContracts ($userID) {

		$sql = "call getContracts($userID)";
		return $this->executenonquery($sql,true);

	}

	function getContractInfo ($contractID) {
		//$sql = "select contracts.*,currency.currency, childContract.ID as childContractID from contracts left join currency on currency.ID =  contracts.currencyID left join contracts childContract on childContract.parentID = contracts.ID where contracts.ID = $contractID";
		$sql = "call getContractInfo($contractID)";
		return $this->executenonquery($sql,true);
	}

	function removeParentIds ($contractID) {
		$sql = "update contracts set parentID = 0 where parentID = $contractID ";
		return $this->executenonquery($sql,true);
	}	

	function getContractsForUpgrade ($contractID,$channelID) {
		//$sql = "select contracts.*,currency.currency, childContract.ID as childContractID from contracts left join currency on currency.ID =  contracts.currencyID left join contracts childContract on childContract.parentID = contracts.ID where contracts.ID = $contractID";
		$sql = "call getContractsForUpgrade($contractID,$channelID)";
		return $this->executenonquery($sql,true);
	}

	
}
?>
