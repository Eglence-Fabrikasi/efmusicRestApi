<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
#[\AllowDynamicProperties]

class artists extends TableItem {
	// fields
	public $ID;
    public $name;
    public $profilePic;
    public $bio;
	public $countryID;
	public $simiiliarArtists;
    public $dateCreated;
    public $createdBy;
    public $email;
	public $appleID;
	public $spotifyID;
	public $ISNI;

	
	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "artists" );
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

	function getArtist ($artistID,$text, $userID) {
		$text = str_replace("'","''",$text);
		$sql = "call getArtist($artistID,'" . $text. "', $userID)";
		return $this->executenonquery($sql,true);

	}
	function artistOldCountry(){
		$sql = "select artists.ID,country.ID as countryID FROM artists inner join country on country.country = artists.country  where artists.country is not null";
		return $this->executenonquery($sql,true);		
	}
	public static function getIsArtisID ($name) {
		$intc = new self();
		$name = str_replace("'","''",$name);
	    $intc->refreshProcedure("select * from artists where name like '$name' COLLATE utf8_turkish_ci limit 1");
	    return $intc;
	}

	public static function getArtistFromName ($name) {
		$intc = new self();
		$name = str_replace("'","''",$name);
		//echo "select * from artists where name='$name' limit 1";
	    $intc->refreshProcedure("select * from artists where name='$name' COLLATE utf8_turkish_ci limit 1");
	    return $intc;
	}

	function getCustomerArtists ($customerID,$artistID=0,$userID=0) {
		$sql = "call getCustomerArtists($customerID,$artistID,$userID)";
		return $this->executenonquery($sql,true);

	}
}
?>
