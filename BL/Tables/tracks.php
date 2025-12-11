<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
class tracks extends TableItem {
	// fields
	public $ID;
	public $title;
	public $isrc;
	public $assetFile;
	public $filesize;
	public $MD5;
	public $duration;
	public $genreID;
	public $explicit;
	public $pricing;
	public $subgenreID;
	public $copyright;
	public $status;
	public $createdBy;
	public $dateCreated;
	public $isPDF;
	public $trackVersion;
	public $musicVideoCoverFile;
	public $musicVideoCoverSize;
	public $musicVideoCoverMD5;
	public $trackLabel;
	public $preorderType;
	public $lyrics;
	public $lpCountry;
	public $lp;
	public $lrc;
	public $previewTime;
	public $djmixes;
	public $relatedISRC;
	public $avRating;
	
	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "tracks" );
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

	function getTracks ($userID, $customerID, $albumID){
		$sql = "call getTracks($userID, $customerID, $albumID)";
		return $this->executenonquery($sql,true);
	}

	function getTrackArtists ($trackID){
		$sql = "call getTrackArtists(".$this->checkInjection($trackID).")";
		return $this->executenonquery($sql,true);
	}

	function getCustomerTracks($userID, $customerID,$trackID){
		$sql = "call getCustomerTracks($userID, $customerID, $trackID)";
		return $this->executenonquery($sql,true);
	}

	function getSearchTracks($customerID, $searchText, $albumID){
		$sql = "call pgetSearchTracks($customerID , '$searchText', $albumID)";
		return $this->executenonquery($sql,true);
	}

	function getTrackInfo($filePath){
		$md5 = hash_file('md5', "/assets".$filePath);
		$filesize = filesize( "/assets".$filePath);
		//echo dirname(dirname(dirname(__FILE__)))."/unix/ffmpeg -i ".("/assets".$filePath)." 2>&1 | grep Duration | awk '{print $2}' | tr -d , ";
		exec(dirname(dirname(dirname(__FILE__)))."/unix/ffmpeg -i ".("/assets".$filePath)." 2>&1 | grep Duration | awk '{print $2}' | tr -d , ", $output);
		//echo "/usr/local/bin/ffmpeg/ffmpeg -i ".$filePath." 2>&1 | grep Duration | awk '{print $2}' | tr -d , ";
		//exec("/usr/local/bin/ffmpeg/ffmpeg -i ".("/assets".$filePath)." 2>&1 | grep Duration | awk '{print $2}' | tr -d , ", $output);
		list($a,$b,$c,$d)=explode(':', str_replace('.', ':', $output[0]));            
		$duration = 0;
		$duration = ($a*60*60)+($b*60)+($c);
		return array($duration, $md5, $filesize);
	}

	function getTrackAlbumsInfo($userID, $customerID, $trackID){
		$sql = "call pgetTrackAlbumsInfo($userID, $customerID, $trackID)";
		return $this->executenonquery($sql,true);
	}
	
	public static function trackCopy ($trackID, $userID) {
	    $intc = new self();
	    $intc->refreshProcedure("call pgetTrackCopy($trackID, $userID)");
	    return $intc;
	}
	
	public static function getTracksFromISRC ($ISRC, $userID) {
	    $intc = new self();
	    $intc->refreshProcedure("select * from tracks where isrc='$ISRC' and createdBy=$userID order by ID desc limit 1");
	    return $intc;
	}
}
?>
