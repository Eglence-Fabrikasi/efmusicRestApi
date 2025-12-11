<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
class channels extends TableItem {
	// fields
	public $ID;
	public $channel;
	public $channelURL;
	public $channelEmail;
	public $channelLogo;
	public $channelColor;
	public $mailServer;
	public $mailUser;
	public $mailPass;
	public $mailPort;
	public $mailFromName;
	public $isActive;
	public $date_;
	public $userID;
	public $googleTagCode;
	public $googleAdCode;
	public $facebookPixelCode;
	public $facebookUrl;
	public $twitterUrl;
	public $instagramUrl;
	public $youtubeUrl;
	public $faqUrl;
	public $appUrl;
	

	// Constructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "channels" );
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
    function getChannels () {
		$sql = "select * from channels where isActive=1";
		return $this->executenonquery($sql,true);
	}
	function getChannelInfo ($channelID) {
		$sql = "select ID, channel, channelURL, channelEmail, channelLogo, channelColor, googleTagCode, googleAdCode, facebookPixelCode, facebookUrl, twitterUrl, instagramUrl, youtubeUrl, faqUrl from channels where ID = $channelID";
		return $this->executenonquery($sql, true);
	}	
}
?>
