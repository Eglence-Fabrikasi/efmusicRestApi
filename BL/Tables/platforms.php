<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
#[\AllowDynamicProperties]

class platforms extends TableItem {
	// fields
	public $ID;
	public $storeName;
	public $isGlobal;
	public $dowload;
	public $stream;
	public $isVisible;
	public $sortOrder;
	public $ftpServer;
	public $ftpUser;
	public $ftpPass;
	public $ftpFolder;
	public $port;
	public $isMusic;
	public $isPlaylist;
	public $isMusicVideo;
	public $mfit;
	public $color;
	public $rgbColor;
	public $refreshToken;
	
	
	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "platforms" );
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

	function getPlatforms () {
		$sql = "select * from platforms where isVisible=1 and (isMusic=1 or isMusicVideo=1) order by sortOrder";
		return $this->executenonquery($sql, true);
	}
	
}
?>
