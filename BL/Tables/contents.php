<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
#[\AllowDynamicProperties]

class contents extends TableItem {
	// fields
	public $ID;
	public $contentStatus;
	public $contentType;
	public $userID;
	public $dateCreated;
	public $createdBy;
	public $dateModified;
	public $modifiedBy;
	public $contentSubTypeID;	
	public $isDeleted;
	public $isOld;
	
	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "contents" );
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

	function getContent ($customerID,$contentID) {

		$sql = "call getContent($customerID,$contentID)";
		return $this->executenonquery($sql,true);

	}

	public static function getContentFromUPC ($UPC,$userID) {
	    $intc = new self();
	    $intc->refreshProcedure("select contents.* from contents 
										inner join albums on albums.contentID=contents.ID
								where 
									albums.upc='$UPC' and contents.userID=$userID order by contents.ID desc limit 1");
	    return $intc;
	}

	public static function getContentFromAlbum ($albumID) {
	    $intc = new self();
	    $intc->refreshProcedure("call getContentFromAlbum($albumID)");
	    return $intc;
	}
	public static function setDBMigration ($step) {
	    $intc = new self();
	    $intc->refreshProcedure("call setDBMigration($step)");
	    return $intc;
	}
	function getReviewsContents ($userID=0) {
		$sql = "call pgetReviewsContents($userID)";
		return $this->executenonquery($sql,true);
	}

	function getContentHistory ($contentID,$customerID) {
		$sql = "call getContentHistory(".$contentID.",".$customerID.")";
		return $this->executenonquery($sql,true);
	}

	function getDeliveryAlbum ($albumID, $userID) {
		$sql = "call pgetDeliveryAlbum($albumID, $userID)";		
		return $this->executenonquery($sql,true);
	}
	public static function getUpc () {
	    $intc = new self();
	    $intc->refreshProcedure("select * from UPC");
	    return $intc;
	}
	public static function getIsrc () {
	    $intc = new self();
	    $intc->refreshProcedure("select * from ISRC");
	    return $intc;
	}
	function setUpc ($UPC) {
		$sql = "update UPC  set `UPC`='$UPC'";
		return $this->executenonquery($sql, false, true);
	}
	function setIsrc ($IsrcNo) {
		$sql = "update ISRC  set `ISRCno`='$IsrcNo'";
		return $this->executenonquery($sql, false, true);
	}
	// public static function setUpc ($UPC) {
	//     $intc = new self();
	//     $intc->refreshProcedure("update UPC  set `UPC`='$UPC'");
	//     return $intc;
	// }
}
?>
