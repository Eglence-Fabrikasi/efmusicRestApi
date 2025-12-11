<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
class localization extends TableItem
{
	//fields
	Public $ID;
	Public $identifier;
	Public $label;
	Public $lang_cd;
	Public $isDeleted;
	
	// Counctructor
	function __construct($ID=NULL)
	{
		parent::__construct();
		$this->ID=$ID;
		$this->settable("localization");
		$this->refresh($ID);
	}
	function __set($property, $value)
	{
		$this->$property = $value;
	}
	function __get($property)
	{
		if (isset($this->$property))
		{
			return $this->$property;
		}
	}
	function label($name,$lang="tr") {
		/*
		$memcache = new Memcache();
		$memcache->connect ( 'localhost', 11211 ) or die ( "Could not connect" );
		$key = md5 ( "SELECT identifier,label FROM localization where lang='".$lang."' and isDeleted=0" );
		//$memcache->delete($key);
		
		$get_result = array ();
		$get_result = $memcache->get ( $key );

		if ($get_result) {
			//echo "Retrieved from the cache\n";
			$locResult=$get_result;
		} else {
			$query = "SELECT identifier,label FROM localization where lang='".$lang."' and isDeleted=0";
			$result = $this->executenonquery ( $query );
			//echo "Retrieved from the Database\n";
			$locResult = array();
			while ( $row = mysqli_fetch_array ( $result ) ){
				$locResult[$row["identifier"]]=$row["label"];
			}
			$memcache->set ( $key, $locResult, MEMCACHE_COMPRESSED, 3600 ); // Store the result of the query for 20 seconds
		}
		$returner = (isset($locResult[$name]) ? $locResult[$name] : "**".$name."**");
		*/
		$qry = "SELECT label FROM localization WHERE lang_cd = '" .$lang . "' AND identifier  = '" . $name . "' and isDeleted<>1 LIMIT 1;";
		$resource = $this->executenonquery($qry);
		if (mysqli_num_rows($resource) != 1) {
			$returner = '**'.$name.'**';
		}
		else {
			$returner = mysqli_fetch_object($resource)->label;
		}
		$returner = nl2br($returner);
		return $returner;
	}

}
?>
