<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
#[\AllowDynamicProperties]

class albumPlatformUrls extends TableItem {
	// fields
	public $ID;
	public $platformID;
	public $albumID;
	public $url;
	public $date_;
	
	
	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "albumPlatformUrls" );
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
	
    public static function getalbumPlatformUrls ($platformID,$albumID) {
        $intc = new self();
        $intc->refreshProcedure("SELECT * from albumPlatformUrls where albumID=$albumID and platformID=$platformID order by ID desc limit 1");
        return $intc;
      
    }
}
?>