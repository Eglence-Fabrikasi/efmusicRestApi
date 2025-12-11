<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
class albums extends TableItem {
	// fields
	public $ID;
	public $contentID;
	public $upc;
	public $artFile;
	public $artFileMD5;
	public $artFileSize;
	public $title;
	public $description;
	public $languageID;
	public $genreID;
	public $subgenreID;
	public $copyright;
	public $releaseDate;
	public $prevReleased;
	public $pricing;
	public $salesStartDate;
	public $status;
	public $preorder;
	public $countryID;
	public $titleVersion;
	public $preorderDate;
	public $labelName;
	public $vendorID;
	public $mfit;
	public $allowPreorderPreview;
	public $tags;
	public $imprint;
	public $printLength;
	public $explicit;
	public $date_;
	public $isCompilation;
	public $numberOfVolumes;
	public $isReleaseLabel;

	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "albums" );
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

	public static function getAlbumByContentID ($contentID) {
		$intc = new self();
		$intc->refreshprocedure("select * from albums where contentID=$contentID order by ID desc limit 1");
		return $intc;
	}

	function getAlbums ($userID,$albumID,$contentType) {
		$sql = "call getAlbums($userID,$albumID,$contentType)";
		return $this->executenonquery($sql,true);
	}

	function getCatalog ($userID, $customerID, $contentType) {
		$sql = "call getCatalog($userID, $customerID, $contentType)";
		return $this->executenonquery($sql,true);
	}

	//Dashboard - Latest Products
	function getLastCatalog ($userID, $customerID, $contentType, $recordLimit) {
		$sql = "call getLastCatalog($userID, $customerID, $contentType, $recordLimit)";
		return $this->executenonquery($sql,true);
	}	

	function getAlbumsbyUPC ($customerID, $UPC) {
		$sql = "call getAlbumsbyUPC($customerID,'".$UPC."')";
		return $this->executenonquery($sql,true);
	}
	function getAlbumsbyID ($userID, $customerID,$albumID) {
		$sql = "call getAlbumsbyID($userID, $customerID,'".$albumID."')";
		return $this->executenonquery($sql,true);
	}
	public static function getAlbumByPdfID ($userID, $contentID) {
		$intc = new self();
		$intc->refreshprocedure("call pgetAlbumByPdfID($userID, $contentID)");
		return $intc;
	}
	function getAlbumBooklet ($userID, $customerID, $albumID) {
		$sql = "call pgetAlbumBooklet($userID, $customerID, $albumID)";
		return $this->executenonquery($sql,true);
	}
	function getAlbumPlatformsStatus ($albumID, $userID, $customerID, $contentType) {
		$sql = "call pgetAlbumPlatformsStatus($albumID, $userID, $customerID, $contentType)";
		return $this->executenonquery($sql,true);
	}
	function getAlbumControl ($customerID, $albumID, $controlType) {
		$sql = "call pgetAlbumControl($customerID, $albumID, $controlType)";
		return $this->executenonquery($sql,true);
	}
	function albumDelete ($userID, $albumID, $contentID, $isOld) {
		$sql = "call palbumDelete($userID, $albumID, $contentID, $isOld)";
		return $this->executenonquery($sql,true);
	}
	function getAlbumSearch ($userID, $customerID, $searchText) {
		$sql = "call pgetAlbumSearch($userID, $customerID, '$searchText')";
		return $this->executenonquery($sql,true);
	}
	function getDeliveredAlbumByTrackID ($trackID, $userID, $customerID) {
		$sql = "call getDeliveredAlbumByTrackID($trackID, $userID, $customerID)";
		return $this->executenonquery($sql,true);
	}
	public static function albumCopy ($albumID, $contentID, $clanguage, $userID) {
	    $intc = new self();
	    $intc->refreshProcedure("call pCopyAlbum($albumID, $contentID, '$clanguage', $userID)");
	    return $intc;
	}
}
?>
