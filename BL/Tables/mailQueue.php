<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
class mailQueue extends TableItem {
	// fields
	public $ID;
	public $sender;
	public $recipient;
	public $subject;
	public $body;
	public $createTime;
	public $sendTime;
	public $recipientEmail;
	public $templateID;
	public $channelID;
	public $readDate;
	
	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "mailQueue" );
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

    function getMails () {
        $sql = "select * from mailQueue where channelID<>5 and sendTime is null and ifnull(recipientEmail,'')<>'' and createTime BETWEEN NOW() - INTERVAL 3 DAY AND NOW()  order by ID limit 20";
        return $this->executenonquery($sql);
    }	

	
	
}
?>
